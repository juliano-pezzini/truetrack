# truetrack
True track My Money is a solution to help the tracking of the personal finances, using accounting techniches to bring the accurate insights about the spendings. 

Follow bellow some of the requirements of the solution:

Functional requirements
- Identificar se as contas bancárias estão em risco de ficarem negativas (pagar juros)
- Identificar quanto foi recebido e gasto em cada período, e se houve "lucro" (receita menos despesa)
  * Projetar as entradas e saídas do mês, baseado em expectativas de receitas e despesas
- Identificar quanto de dinheiro foi investido no período
- Identificar quanto vai ser a fatura do cartão para saber se haverá dinheiro que chega para pagar a conta
- Identificar onde/com o que está sendo gasto o dinheiro (podem estar havendo excessos)
- Identificar quanto os investimentos estão rendendo

Segue abaixo algumas técnicas/estruturas utilizadas para alcançar os objetivos acima:

* Todos as despesas e receitas são registradas como lançamentos, atribuídos a "Categorias de Receita/Despesa", e também a "Contas de movimentação" (que podem ser contas bancárias, cartões de créditos, carteiras de bolso, ou mesmo contas transitórias). Os lançamentos também podem ser atribuídos à "Tags", que são uma forma de agrupar determinados lançamentos para futura gestão/observação.
Por fim, os lançamentos também são identificados com as datas em que ocorreram, e também datas em que foram efetivamente liquidados (pagos/recebidos).

* As contas do tipo "Cartão de crédito" precisam ser zeradas mensalmente contra alguma outra conta de movimento (que significa o pagamento da fatura do cartão).

* Periodicamente deverão ser efetuadas conciliaçÕes dos extratos bancários (das contas do tipo contas bancárias), e também das faturas dos cartões de crédito.
