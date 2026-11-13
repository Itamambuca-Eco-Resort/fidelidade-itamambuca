# Fidelidade Itamambuca

Programa de pontos e recompensas do Itamambuca Eco Resort. Roda na raiz de `fidelidade.itamambucaecoresort.com.br`. Os links internos são absolutos (`/perfil.php`, `/assets/...`).

## Estrutura

```
.
├── *.php            páginas públicas
├── api/             endpoints JSON
├── auth/            login, cadastro, Google OAuth
├── dashboard/       painel administrativo de parceiros
├── models/          classes de dados
├── includes/        header/footer
├── config/          config.php, database.php, secrets.php (fora do git)
├── assets/          CSS, JS, imagens, ícones PWA
├── uploads/         envios de usuários (fora do git)
├── database/        SQL (bloqueado na web)
├── scripts/         manutenção, migrações, geração de ícones (bloqueado na web)
├── manifest.json sw.js offline.html   PWA
└── htaccess         modelo antigo, INATIVO (sem ponto no nome)
```

**Páginas:** `badges.php`, `busca.php`, `categoria.php`, `checkin.php`, `checkin-staff.php`, `cupons.php`, `favoritos.php`, `informacoes-uteis.php`, `missoes.php`, `perfil.php`, `ranking.php`, `recompensas.php`, `recreation.php`, `scan.php`, `servico.php`, `sobre-hotel.php`, `logout.php` (atalho para `auth/logout.php`).

> ⚠️ **Falta o `index.php` (página inicial).** O arquivo desta cópia era o da Agenda do SPA. A versão da Fidelidade precisa ser copiada do servidor.

## Configuração

Copie `config/secrets.example.php` para `config/secrets.php` (bancos da Fidelidade e da Recreação).
