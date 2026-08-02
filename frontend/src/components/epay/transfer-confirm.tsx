import { CheckCircle2, Loader2, ShieldCheck, WalletCards } from "lucide-react"
import * as React from "react"

import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Separator } from "@/components/ui/separator"

type TransferKind = "red" | "red-wx" | "wxtrans"
type TransferPayload = Record<string, string>

export type TransferConfirmConfig = {
  kind?: TransferKind
  sitename?: string
  amount?: string | number
  createdAt?: string
  tip?: string
  endpoint?: string
  payload?: TransferPayload
  wxTransfer?: Record<string, string>
  successUrl?: string
}

type TransferResult = {
  code?: number | string
  msg?: string
  redirect_url?: string
  wxtransfer?: Record<string, string>
}

type EpayWindow = Window & {
  WeixinJSBridge?: {
    invoke: (
      name: string,
      payload: Record<string, string>,
      callback: (result: { err_msg?: string }) => void
    ) => void
  }
  __epayWxReady?: boolean
  __epayWxError?: string
}

function isWechat(kind: TransferKind) {
  return kind === "red-wx" || kind === "wxtrans"
}

export function TransferConfirmView({
  config = {},
}: {
  config?: TransferConfirmConfig
}) {
  const kind = config.kind ?? "red"
  const wechat = isWechat(kind)
  const [loading, setLoading] = React.useState(false)
  const [wxReady, setWxReady] = React.useState(
    () => !wechat || Boolean((window as EpayWindow).__epayWxReady)
  )
  const [error, setError] = React.useState("")

  React.useEffect(() => {
    if (!wechat) return
    const onWxState = () => {
      const browserWindow = window as EpayWindow
      setWxReady(Boolean(browserWindow.__epayWxReady))
      if (browserWindow.__epayWxError) {
        setError(browserWindow.__epayWxError)
      }
    }
    onWxState()
    window.addEventListener("epay-wx-state", onWxState)
    return () => window.removeEventListener("epay-wx-state", onWxState)
  }, [wechat])

  const finishWechatTransfer = (
    wxTransfer: Record<string, string>,
    redirectUrl: string
  ) => {
    const bridge = (window as EpayWindow).WeixinJSBridge
    if (!bridge) {
      setLoading(false)
      setError("微信支付能力尚未就绪，请稍后重试。")
      return
    }
    bridge.invoke("requestMerchantTransfer", wxTransfer, (result) => {
      setLoading(false)
      if (result.err_msg === "requestMerchantTransfer:ok") {
        window.location.href = redirectUrl
      } else {
        setError("收款未完成，请稍后重试。")
      }
    })
  }

  const submit = async () => {
    if (loading) return
    if (wechat && !wxReady) {
      setError("正在准备微信收款能力，请稍后重试。")
      return
    }
    setError("")
    setLoading(true)

    if (kind === "wxtrans") {
      finishWechatTransfer(config.wxTransfer ?? {}, config.successUrl ?? "")
      return
    }

    try {
      const response = await fetch(config.endpoint ?? "./red_ajax.php", {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(config.payload ?? {}).toString(),
      })
      if (!response.ok) throw new Error("request failed")
      const result = (await response.json()) as TransferResult
      if (String(result.code) !== "0") {
        setError(result.msg ?? "收款失败，请稍后重试。")
        setLoading(false)
        return
      }
      if (result.wxtransfer) {
        finishWechatTransfer(result.wxtransfer, result.redirect_url ?? "")
      } else if (result.redirect_url) {
        window.location.href = result.redirect_url
      } else {
        setLoading(false)
        setError("收款结果缺少跳转地址，请稍后重试。")
      }
    } catch {
      setLoading(false)
      setError("网络异常，请稍后重试。")
    }
  }

  const isWxTransfer = kind === "wxtrans"
  const title = isWxTransfer ? "待你收款" : "待你领取红包"
  const description = isWxTransfer
    ? "确认后，资金将通过微信商家转账到账。"
    : "确认收款后，红包会按当前支付渠道到账。"
  const actionLabel = isWxTransfer ? "确认收款" : "收款"
  const siteName = config.sitename ?? "Rainbow Pay"

  return (
    <div className="flex min-h-svh items-center justify-center bg-muted/30 px-4 py-8 text-foreground sm:px-6">
      <Card className="w-full max-w-lg animate-in rounded-3xl shadow-lg duration-500 fade-in slide-in-from-bottom-3">
        <CardHeader className="items-center space-y-4 p-7 text-center sm:p-10">
          <div className="flex size-16 items-center justify-center rounded-full bg-primary/10 text-primary">
            <WalletCards className="size-8" />
          </div>
          <div>
            <Badge variant="secondary" className="rounded-lg">
              <ShieldCheck className="mr-1.5 size-3.5 text-emerald-600" />
              安全收款
            </Badge>
            <CardTitle className="mt-4 text-2xl tracking-tight">
              {title}
            </CardTitle>
            <CardDescription className="mx-auto mt-2 max-w-sm leading-6">
              {description}
            </CardDescription>
          </div>
          <p className="text-4xl font-semibold tracking-tight text-primary">
            ¥ {String(config.amount ?? "0.00")}
          </p>
        </CardHeader>
        <CardContent className="space-y-5 px-7 pb-7 sm:px-10">
          <div className="rounded-2xl border bg-muted/30 p-4 text-sm">
            <div className="flex justify-between gap-4 py-2">
              <span className="text-muted-foreground">
                {isWxTransfer ? "转账时间" : "创建时间"}
              </span>
              <span className="text-right">{config.createdAt ?? "—"}</span>
            </div>
            <Separator />
            <div className="flex justify-between gap-4 py-2">
              <span className="text-muted-foreground">收款方式</span>
              <span className="font-medium">
                {isWxTransfer ? "微信商家转账" : "红包收款"}
              </span>
            </div>
          </div>
          {error && (
            <Alert variant="destructive" className="rounded-2xl">
              <AlertTitle>操作未完成</AlertTitle>
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}
          <Button
            id="Confirm"
            type="button"
            className="h-12 w-full rounded-xl"
            disabled={loading || (wechat && !wxReady)}
            onClick={submit}
          >
            {loading ? (
              <Loader2 className="mr-2 size-4 animate-spin" />
            ) : (
              <CheckCircle2 className="mr-2 size-4" />
            )}
            {loading
              ? "处理中"
              : wechat && !wxReady
                ? "准备微信收款"
                : actionLabel}
          </Button>
          <p className="text-center text-xs text-muted-foreground">
            {config.tip ?? "请在24小时内确认"}
          </p>
        </CardContent>
        <CardFooter className="justify-center border-t p-6 text-xs text-muted-foreground">
          {siteName} · © {new Date().getFullYear()}
        </CardFooter>
      </Card>
    </div>
  )
}
