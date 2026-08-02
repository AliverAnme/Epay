import * as React from "react"
import { ArrowUpRight, CircleCheck, CircleX, ShieldCheck } from "lucide-react"

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

export type GoldPlanConfig = {
  amount?: string | number
  error?: string
  jumpUrl?: string
  sitename?: string
}

function sendJumpOut(jumpUrl?: string) {
  if (!jumpUrl || typeof window === "undefined") return
  window.parent.postMessage(
    JSON.stringify({ action: "jumpOut", jumpOutUrl: jumpUrl }),
    "https://payapp.weixin.qq.com"
  )
}

export function GoldPlanView({ config }: { config?: GoldPlanConfig }) {
  const error = config?.error?.trim()
  const sitename = config?.sitename || "Rainbow Pay"

  React.useEffect(() => {
    if (typeof window === "undefined") return
    window.parent.postMessage(
      JSON.stringify({
        action: "onIframeReady",
        displayStyle: "SHOW_CUSTOM_PAGE",
      }),
      "https://payapp.weixin.qq.com"
    )
    if (!error) sendJumpOut(config?.jumpUrl)
  }, [config?.jumpUrl, error])

  return (
    <main className="flex min-h-svh items-center justify-center bg-muted/30 p-4 sm:p-8">
      <Card className="w-full max-w-lg rounded-3xl border bg-card/95 shadow-xl shadow-primary/5 backdrop-blur">
        <CardHeader className="gap-4 text-center sm:text-left">
          <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground sm:justify-start">
            <ShieldCheck className="size-4 text-primary" aria-hidden="true" />
            <span>{sitename}</span>
            <Badge variant="secondary" className="rounded-full">
              微信支付
            </Badge>
          </div>
          <CardTitle className="text-2xl tracking-tight">
            {error ? "支付暂时无法继续" : "支付结果"}
          </CardTitle>
          <CardDescription>
            {error
              ? "请返回上一页检查订单信息。"
              : "订单已完成处理，请返回商家页面查看最新状态。"}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {error ? (
            <Alert variant="destructive" className="rounded-2xl">
              <CircleX className="size-4" aria-hidden="true" />
              <AlertTitle>订单处理异常</AlertTitle>
              <AlertDescription className="break-words">
                {error}
              </AlertDescription>
            </Alert>
          ) : (
            <div className="rounded-2xl border border-primary/20 bg-primary/5 p-5 text-center">
              <CircleCheck
                className="mx-auto size-12 text-primary"
                aria-hidden="true"
              />
              <p className="mt-3 text-3xl font-semibold tracking-tight">
                ¥{config?.amount || "0.00"}
              </p>
              <p className="mt-1 text-sm text-muted-foreground">支付成功</p>
            </div>
          )}
        </CardContent>
        {!error && config?.jumpUrl ? (
          <CardFooter>
            <Button
              className="h-11 w-full rounded-xl"
              onClick={() => sendJumpOut(config.jumpUrl)}
            >
              返回商家页面
              <ArrowUpRight data-icon="inline-end" aria-hidden="true" />
            </Button>
          </CardFooter>
        ) : null}
      </Card>
    </main>
  )
}
