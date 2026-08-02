# Epay UI

This package is the React frontend layer for Rainbow Epay. It is scaffolded by
the official shadcn CLI (`shadcn init -t vite -b radix`) and uses the generated
components under `src/components/ui/` rather than a hand-written imitation.

## Architecture

- `src/components/ui/` — shadcn/ui primitives generated with the CLI.
- `src/components/epay/epay-app.tsx` — Epay page compositions built from those primitives.
- `src/components/epay/public-home.tsx` — public landing page, using the same tokens and primitives.
- `src/components/epay/pay-page.tsx` and `payment-status.tsx` — customer amount entry and payment result states; the existing payment AJAX contract is loaded after the component mount.
- `src/components/epay/transfer-confirm.tsx` — red-packet and WeChat merchant-transfer confirmation, preserving the original AJAX/WeChat bridge contracts.
- `src/main.tsx` — dual entry: the local Vite preview mounts `#root`, while PHP
  pages mount `#epay-react-root` with `data-epay-view` and an optional JSON
  `data-epay-config` payload.
- `../assets/dist/` — production bundle consumed by PHP entry points.

The public home, admin/merchant workspaces, authentication views, cashier,
customer payment page, and payment result states are mounted through the bridge. Other PHP screens stay on their existing
business logic and are rendered inside the same responsive workspace shell so
they can be migrated incrementally without changing payment endpoints.

## Commands

```bash
pnpm install
pnpm dev
pnpm lint
pnpm typecheck
pnpm build
```

`pnpm build` emits `assets/dist/epay-ui.js` and `assets/dist/epay-ui.css` so a
PHP deployment does not need Node at runtime.

## Add a native shadcn component

Run the official CLI from this directory, then compose the generated component
in an Epay feature module:

```bash
pnpm dlx shadcn@latest add dialog
```

Keep component composition in `src/components/epay/` and keep business/data
access in the existing PHP endpoints.
