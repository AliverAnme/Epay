import { StrictMode } from "react"
import { createRoot } from "react-dom/client"

import "./index.css"
import App from "./App.tsx"
import { EpayApp } from "@/components/epay/epay-app"
import { ThemeProvider } from "@/components/theme-provider.tsx"

const mount =
  document.getElementById("root") ?? document.getElementById("epay-react-root")
const view = (mount?.dataset.epayView ?? "admin-dashboard") as
  | "admin-dashboard"
  | "admin-shell"
  | "merchant-dashboard"
  | "merchant-shell"
  | "cashier"
  | "payment"
  | "public-home"
  | "test-payment"
  | "payment-status"
  | "transfer-confirm"
  | "pay-page"
  | "admin-login"
  | "user-login"
  | "user-register"
  | "user-recovery"
const config = mount?.dataset.epayConfig
  ? (() => {
      try {
        return JSON.parse(mount.dataset.epayConfig!)
      } catch {
        return undefined
      }
    })()
  : undefined

if (!mount) throw new Error("Epay UI mount point was not found")

createRoot(mount).render(
  <StrictMode>
    <ThemeProvider>
      {mount.id === "root" ? (
        <App />
      ) : (
        <EpayApp
          view={view}
          config={config as Record<string, unknown> | undefined}
        />
      )}
    </ThemeProvider>
  </StrictMode>
)

if (view === "pay-page") {
  let mountAttempts = 0
  const notifyMounted = () => {
    if (document.getElementById("keyboard")) {
      window.dispatchEvent(new Event("epay-ui-mounted"))
      return
    }
    if (mountAttempts++ > 120) return
    window.requestAnimationFrame(notifyMounted)
  }
  window.requestAnimationFrame(notifyMounted)
}

if (
  mount.id === "epay-react-root" &&
  (view === "admin-shell" || view === "merchant-shell")
) {
  window.setTimeout(() => {
    const slot = document.getElementById("epay-react-legacy-slot")
    const source = document.getElementById("epay-react-legacy-source")
    if (!slot) return
    const nodes = source
      ? Array.from(source.childNodes)
      : Array.from(document.body.childNodes)
    nodes.forEach((node) => {
      if (
        node === mount ||
        (node.nodeType === Node.ELEMENT_NODE &&
          (node as Element).tagName === "SCRIPT")
      )
        return
      if (node.nodeType === Node.TEXT_NODE && !node.textContent?.trim()) return
      slot.appendChild(node)
    })
    source?.remove()
  }, 0)
}
