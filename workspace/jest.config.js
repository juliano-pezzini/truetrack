export default {
  testEnvironment: 'jsdom',
  
  // Setup files
  setupFilesAfterEnv: ['<rootDir>/jest.setup.js'],
  
  // Module paths
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/resources/js/$1',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
  },
  
  // Transform files
  transform: {
    '^.+\\.(js|jsx)$': ['babel-jest', { configFile: './babel.config.cjs' }],
  },
  
  // Test match patterns
  testMatch: [
    '<rootDir>/resources/js/**/__tests__/**/*.{js,jsx}',
    '<rootDir>/resources/js/**/*.{spec,test}.{js,jsx}',
  ],
  
  // Coverage configuration
  collectCoverageFrom: [
    'resources/js/**/*.{js,jsx}',
    '!resources/js/**/*.d.ts',
    '!resources/js/**/__tests__/**',
    '!resources/js/**/index.js',
  ],
  
  coverageThreshold: {
    global: {
      branches: 50,
      functions: 50,
      lines: 50,
      statements: 50,
    },
  },
  
  // Ignore patterns
  testPathIgnorePatterns: [
    '/node_modules/',
    '/vendor/',
    '/storage/',
  ],
  
  moduleFileExtensions: ['js', 'jsx', 'json'],
};
