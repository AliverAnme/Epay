import { CheckCircle2, CircleAlert, ShieldCheck } from "lucide-react"

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

export type PaymentStatusConfig = {
  status?: "success" | "error" | "red-success"
  sitename?: string
  message?: string
  amount?: string | number
  recipient?: string
  tradeNo?: string
  createdAt?: string
  paidAt?: string
  receiveAction?: string
  receiveName?: string
}

function closeWindow() {
  const browserWindow = window as Window & {
    AlipayJSBridge?: { call: (name: string) => void }
    WeixinJSBridge?: { call: (name: string) => void }
    mqq?: { ui?: { popBack?: () => void } }
  }
  if (browserWindow.AlipayJSBridge)
    return browserWindow.AlipayJSBridge.call("popWindow")
  if (browserWindow.WeixinJSBridge)
    return browserWindow.WeixinJSBridge.call("closeWindow")
  if (browserWindow.mqq?.ui?.popBack) return browserWindow.mqq.ui.popBack()
  window.opener = null
  window.close()
}

export function PaymentStatusView({
  config = {},
}: {
  config?: PaymentStatusConfig
}) {
  const success = config.status !== "error"
  const red = config.status === "red-success"
  const siteName = config.sitename ?? "Rainbow Pay"
  const title = success ? (red ? "红包领取成功" : "支付成功") : "操作未完成"
  const description = success
    ? red
      ? `你已收款，资金${config.receiveAction ?? "将稍后存入"}${config.receiveName ?? "零钱"}`
      : "订单已完成支付，收款方将按流程入账。"
    : (config.message ?? "系统暂时无法完成这次操作，请返回后重试。")
  const Icon = success ? CheckCircle2 : CircleAlert
  return (
    <div className="flex min-h-svh items-center justify-center bg-muted/30 px-4 py-8 text-foreground sm:px-6">
      <Card className="w-full max-w-lg animate-in rounded-3xl shadow-lg duration-500 fade-in slide-in-from-bottom-3">
        <CardHeader className="items-center space-y-4 p-7 text-center sm:p-10">
          <div
            className={
              success
                ? "flex size-16 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600"
                : "flex size-16 items-center justify-center rounded-full bg-destructive/10 text-destructive"
            }
          >
            <Icon className="size-8" />
          </div>
          <div>
            <Badge variant="secondary" className="rounded-lg">
              <ShieldCheck className="mr-1.5 size-3.5" />
              安全支付
            </Badge>
            <CardTitle className="mt-4 text-2xl tracking-tight">
              {title}
            </CardTitle>
            <CardDescription className="mx-auto mt-2 max-w-sm leading-6">
              {description}
            </CardDescription>
          </div>
          {success && (
            <p className="text-4xl font-semibold tracking-tight text-primary">
              ¥ {String(config.amount ?? "0.00")}
            </p>
          )}
        </CardHeader>
        {success && (
          <CardContent className="px-7 pb-7 sm:px-10">
            <div className="rounded-2xl border bg-muted/30 p-4 text-sm">
              <div className="flex justify-between gap-4 py-2">
                <span className="text-muted-foreground">收款方</span>
                <strong className="max-w-[240px] truncate text-right">
                  {config.recipient ?? "—"}
                </strong>
              </div>
              <Separator />
              <div className="flex justify-between gap-4 py-2">
                <span className="text-muted-foreground">
                  {red ? "创建时间" : "完成时间"}
                </span>
                <span className="text-right">{config.createdAt ?? "—"}</span>
              </div>
              {!red && (
                <>
                  <Separator />
                  <div className="flex justify-between gap-4 py-2">
                    <span className="text-muted-foreground">订单号</span>
                    <span className="max-w-[240px] truncate text-right font-mono text-xs">
                      {config.tradeNo ?? "—"}
                    </span>
                  </div>
                </>
              )}
              {red && (
                <>
                  <Separator />
                  <div className="flex justify-between gap-4 py-2">
                    <span className="text-muted-foreground">收款时间</span>
                    <span className="text-right">{config.paidAt ?? "—"}</span>
                  </div>
                </>
              )}
            </div>
          </CardContent>
        )}
        <CardFooter className="flex-col gap-4 border-t p-6">
          <Button
            type="button"
            variant={success ? "outline" : "default"}
            className="h-11 w-full rounded-xl"
            onClick={closeWindow}
          >
            {success ? "关闭页面" : "返回"}
          </Button>
          <p className="text-xs text-muted-foreground">
            {siteName} · © {new Date().getFullYear()}
          </p>
        </CardFooter>
      </Card>
    </div>
  )
}
