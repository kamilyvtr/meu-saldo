# Meu Saldo

> Back completo, front simples. Uma aplicação web de gestão financeira pessoal focada em usabilidade, segurança e performance.

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PWA](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=flat-square&logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)

## Sobre o Projeto

O Meu Saldo é uma aplicação PWA (Progressive Web App) desenvolvida para simplificar o controle financeiro pessoal. O sistema permite registrar despesas em poucos toques, gerenciar cartões de crédito sem duplicar despesas (Regra de Ouro), acompanhar metas, orçamentos e visualizar a saúde financeira em dashboards intuitivos.

## Funcionalidades Principais

- **Dashboard Inteligente:** Visão clara de saldos, receitas, despesas e fluxo de caixa.
- **Entrada Rápida:** Registro de transações otimizado para mobile (até 3 toques).
- **Cartões de Crédito:** Controle manual de limites, compras parceladas e faturas. O pagamento da fatura quita a dívida sem duplicar a despesa operacional.
- **Classificação Automática:** Sugestão de categorias baseada em regras e frequência de uso.
- **Metas e Orçamentos:** Definição de limites de gastos por categoria e objetivos financeiros.
- **Despesas Recorrentes:** Lançamentos automáticos mensais (ex: assinaturas e contas fixas).
- **Interface Adaptável:** Design responsivo e suporte nativo a temas Claro e Escuro.
- **PWA:** Instalável em iOS e Android diretamente pelo navegador.

## Arquitetura e Segurança

- Valores monetários armazenados estritamente como `integer` (centavos) para evitar erros de ponto flutuante.
- Isolamento rigoroso de dados (Policies) garantindo que um usuário nunca acesse informações de outro.
- Service Worker configurado como `NetworkOnly` para rotas financeiras, garantindo que dados sensíveis não sejam cacheados no dispositivo.
- Proteção contra IDOR, CSRF e XSS.

## Como Rodar Localmente

### Pré-requisitos

- PHP 8.3+
- Composer
- Node.js 20+

### Passo a Passo

1. Clone o repositório.
2. Instale as dependências do backend: `composer install`
3. Instale as dependências do frontend: `npm install`
4. Configure o ambiente: `cp .env.example .env` e `php artisan key:generate`
5. Crie o banco SQLite e rode as migrations: `touch database/database.sqlite` e `php artisan migrate --seed`
6. Compile os assets e inicie o servidor: `npm run build` e `php artisan serve`

Acesse `http://127.0.0.1:8000`. Credenciais padrão: `admin@meusaldo.com` / `Admin@123456`.

## Instalação no Dispositivo (PWA)

1. Acesse o sistema pelo navegador do celular.
2. No iOS (Safari): Toque em "Compartilhar" e depois em "Adicionar à Tela de Início".
3. No Android (Chrome): Toque nos três pontos e selecione "Instalar aplicativo".

## Licença

Este projeto é de uso pessoal e os direitos autorais são reservados exclusivamente ao autor. É proibida a cópia, distribuição ou modificação sem autorização prévia.
Copyright (c) 2026. Todos os direitos reservados.
