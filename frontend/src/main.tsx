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
  | "admin-order"
  | "admin-resource"
  | "admin-form"
  | "admin-stats"
  | "admin-token"
  | "admin-maintenance"
  | "admin-settle-batch"
  | "admin-account"
  | "admin-group-purchase"
  | "admin-channel-config"
  | "admin-channel-test"
  | "admin-totp"
  | "admin-batches"
  | "admin-roll-config"
  | "admin-shell"
  | "merchant-dashboard"
  | "merchant-shell"
  | "cashier"
  | "payment"
  | "public-home"
  | "test-payment"
  | "payment-status"
  | "transfer-confirm"
  | "gold-plan"
  | "legacy-auth"
  | "gateway-shell"
  | "installer-shell"
  | "public-legacy-shell"
  | "documentation-shell"
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

// 兼容旧版 Bootstrap 的 html/body 全局样式；CSS 仍通过 :has() 提供首屏兜底。
document.documentElement.classList.add("epay-ui-document")

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

// 旧版 Geetest/Bootstrap 代码通过 jQuery `.show()` 切换少数节点。
// Tailwind 的 hidden 工具类带有 important，因此同步 inline display 状态并移除
// 这些旧版节点上的 hidden，避免验证码加载状态被壳层样式再次压住。
const legacyDisplayObserver = new MutationObserver((records) => {
  records.forEach((record) => {
    if (
      record.type !== "attributes" ||
      !(record.target instanceof HTMLElement)
    ) {
      return
    }
    const element = record.target
    const legacyOwned =
      element.id === "captcha_wait" ||
      element.id === "wait" ||
      element.id === "totp-form" ||
      Boolean(element.closest("#epay-react-legacy-slot"))
    if (
      legacyOwned &&
      element.style.display &&
      element.style.display !== "none"
    ) {
      element.classList.remove("hidden")
      if (element.id === "totp-form") element.style.removeProperty("display")
    }
  })
})
legacyDisplayObserver.observe(document.body, {
  subtree: true,
  attributes: true,
  attributeFilter: ["style"],
})

if (view === "pay-page") {
  let mountAttempts = 0
  const notifyMounted = () => {
    const payPageReady = ["keyboard", "amount", "clearBtn", "payBtn"].every(
      (id) => document.getElementById(id)
    )
    if (payPageReady) {
      ;(window as Window & { __epayUiMounted?: boolean }).__epayUiMounted = true
      document.dispatchEvent(new Event("epay-ui-mounted"))
      return
    }
    if (mountAttempts++ > 120) return
    window.requestAnimationFrame(notifyMounted)
  }
  window.requestAnimationFrame(notifyMounted)
}

if (
  mount.id === "epay-react-root" &&
  (view === "admin-shell" ||
    view === "merchant-shell" ||
    view === "legacy-auth" ||
    view === "gateway-shell" ||
    view === "installer-shell" ||
    view === "public-legacy-shell")
) {
  let legacyMoveAttempts = 0
  const moveLegacyContent = () => {
    const slot = document.getElementById("epay-react-legacy-slot")
    const source = document.getElementById("epay-react-legacy-source")
    if (!slot) {
      if (legacyMoveAttempts++ < 120) {
        window.requestAnimationFrame(moveLegacyContent)
      }
      return
    }
    const nodes = source
      ? Array.from(source.childNodes)
      : Array.from(document.body.childNodes)
    nodes.forEach((node) => {
      if (
        node === mount ||
        (!source &&
          node.nodeType === Node.ELEMENT_NODE &&
          (node as Element).tagName === "SCRIPT")
      )
        return
      if (node.nodeType === Node.TEXT_NODE && !node.textContent?.trim()) return
      slot.appendChild(node)
    })
    source?.remove()
  }
  window.requestAnimationFrame(moveLegacyContent)
}
