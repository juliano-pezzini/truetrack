# Fix 419 Error - CSRF Token Authentication in Codespaces

## Problema
Ao tentar criar uma conta no ambiente Codespaces, ocorria o erro 419 (CSRF Token Mismatch). Este erro acontecia porque as configurações de cookies e sessões não estavam otimizadas para ambientes de desenvolvimento em containers com domínios dinâmicos.

## Causa Raiz
Em ambientes Codespaces.github.dev, os cookies de sessão não estavam sendo enviados corretamente devido a:
1. Falta de configuração `SESSION_SECURE_COOKIE=true` (necessário para HTTPS)
2. Falta de configuração `SESSION_SAME_SITE=none` (necessário para domínios cross-site)
3. Falta de configuração do Sanctum para domínios stateful
4. Ausência do middleware TrustProxies para reconhecer proxies reversos
5. Configuração incompleta do Axios no app.jsx do Inertia

## Soluções Aplicadas

### 1. Configurações no `.env`
**Arquivo:** `/workspaces/truetrack/workspace/.env`

```env
# Configurações de Sessão para Codespaces
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none

# Sanctum Configuration para Codespaces
SANCTUM_STATEFUL_DOMAINS=humble-xylophone-x5477pq6p6763rx5-80.app.github.dev
```

**Explicação:**
- `SESSION_SECURE_COOKIE=true`: Garante que cookies sejam enviados apenas via HTTPS (obrigatório no Codespaces)
- `SESSION_SAME_SITE=none`: Permite que cookies sejam enviados em contextos cross-site (necessário para subdomínios do GitHub)
- `SANCTUM_STATEFUL_DOMAINS`: Define o domínio do Codespaces como confiável para autenticação stateful

### 2. Middleware TrustProxies
**Arquivo:** `/workspaces/truetrack/workspace/app/Http/Middleware/TrustProxies.php`

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    protected $proxies = '*';

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
```

**Explicação:**
- `$proxies = '*'`: Confia em todos os proxies (adequado para Codespaces)
- Headers configurados: Reconhece headers de proxy reverso para identificar corretamente o protocolo (HTTP/HTTPS) e domínio original

### 3. Registro do Middleware TrustProxies
**Arquivo:** `/workspaces/truetrack/workspace/bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    // Trust proxies para Codespaces/reverse proxy environments
    $middleware->use([
        \App\Http\Middleware\TrustProxies::class,
    ]);
    
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
        \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
    ]);
    
    // ... resto da configuração
})
```

**Explicação:**
- Registra o TrustProxies globalmente para todas as requisições

### 4. Configuração do Axios no Inertia
**Arquivo:** `/workspaces/truetrack/workspace/resources/js/app.jsx`

```javascript
import axios from 'axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Get CSRF token from meta tag
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;

// Configure Axios for Inertia requests
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.withCredentials = true;
    axios.defaults.withXSRFToken = true;
}
```

**Explicação:**
- Configura o Axios explicitamente no app.jsx (além do bootstrap.js) para garantir que o token CSRF seja enviado em todas as requisições do Inertia
- `withCredentials: true`: Permite envio de cookies em requisições cross-origin
- `withXSRFToken: true`: Habilita o envio automático do token XSRF

## Comandos Executados Após as Mudanças

```bash
# Limpar todos os caches
cd /workspaces/truetrack/workspace
docker compose exec truetrack php artisan config:clear
docker compose exec truetrack php artisan route:clear
docker compose exec truetrack php artisan cache:clear

# Rebuild do frontend
npm run build
```

## Verificação

### Testes Automatizados
```bash
docker compose exec truetrack php artisan test --filter=AccountController
```

**Resultado:** ✅ Todos os 12 testes passaram

### Teste Manual
1. Acesse a aplicação em: https://humble-xylophone-x5477pq6p6763rx5-80.app.github.dev
2. Faça login
3. Navegue até "Accounts" > "Create Account"
4. Preencha o formulário:
   - Name: Teste Cartão
   - Type: Credit Card
   - Initial Balance: -1000.00
   - Description: Cartão de teste
   - Active: Yes
5. Clique em "Create Account"
6. ✅ A conta deve ser criada com sucesso sem erro 419

## Arquivos Modificados

1. `/workspaces/truetrack/workspace/.env` - Configurações de sessão e Sanctum
2. `/workspaces/truetrack/workspace/app/Http/Middleware/TrustProxies.php` - CRIADO
3. `/workspaces/truetrack/workspace/bootstrap/app.php` - Registro do TrustProxies
4. `/workspaces/truetrack/workspace/resources/js/app.jsx` - Configuração do Axios

## Notas Importantes

### Para Desenvolvimento Local (não Codespaces)
Se você estiver desenvolvendo localmente (não no Codespaces), pode usar configurações mais restritivas:

```env
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### Para Produção
Em produção, ajuste as configurações conforme seu domínio:

```env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=.seudominio.com
SANCTUM_STATEFUL_DOMAINS=seudominio.com,www.seudominio.com
```

E no TrustProxies, especifique os IPs dos proxies confiáveis em vez de `*`.

## Referências
- [Laravel CSRF Protection](https://laravel.com/docs/11.x/csrf)
- [Laravel Sanctum SPA Authentication](https://laravel.com/docs/11.x/sanctum#spa-authentication)
- [Laravel Session Configuration](https://laravel.com/docs/11.x/session)
- [Inertia.js CSRF Protection](https://inertiajs.com/csrf-protection)
- [GitHub Codespaces Documentation](https://docs.github.com/en/codespaces)
