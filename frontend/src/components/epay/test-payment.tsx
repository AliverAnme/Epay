import { ArrowLeft, CheckCircle2, CreditCard, ShieldCheck } from "lucide-react"

import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"

export type TestPaymentConfig = {
  sitename?: string
  csrf_token?: string
  trade_no?: string
  money?: string | number
  paid?: boolean
  captcha?: boolean
  paytype?: Array<{ id: string | number; name?: string; showname?: string }>
}

export function TestPaymentView({
  config = {},
}: {
  config?: TestPaymentConfig
}) {
  const submit = (target: HTMLButtonElement) => {
    const legacyWindow = window as Window & {
      submitPay?: (element: HTMLButtonElement) => void
    }
    legacyWindow.submitPay?.(target)
  }
  return (
    <div className="min-h-svh bg-muted/30 px-4 py-8 text-foreground sm:px-6 lg:py-12">
      <div className="mx-auto max-w-2xl">
        <div className="mb-6 flex items-center justify-between gap-3">
          <a
            href="/"
            className="flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
          >
            <ArrowLeft className="size-4" />
            返回首页
          </a>
          <Badge variant="secondary" className="rounded-lg">
            <ShieldCheck className="text-primary" data-icon="inline-start" />
            测试环境
          </Badge>
        </div>
        <Card className="rounded-2xl shadow-sm">
          <CardHeader className="border-b">
            <div className="flex items-center justify-between gap-3">
              <div>
                <CardTitle className="text-xl">支付测试</CardTitle>
                <p className="mt-1 text-sm text-muted-foreground">
                  使用测试订单验证支付通道与回调链路。
                </p>
              </div>
              <div className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <CreditCard className="size-5" />
              </div>
            </div>
          </CardHeader>
          <CardContent className="p-5 sm:p-7">
            <form name="alipayment" className="flex flex-col gap-5">
              <input
                type="hidden"
                name="csrf_token"
                value={config.csrf_token ?? ""}
              />
              <FieldGroup className="grid gap-4 sm:grid-cols-2">
                <Field>
                  <FieldLabel htmlFor="trade_no">商户订单号</FieldLabel>
                  <Input
                    id="trade_no"
                    name="trade_no"
                    value={config.trade_no ?? ""}
                    disabled
                    readOnly
                  />
                </Field>
                <Field>
                  <FieldLabel htmlFor="name">商品名称</FieldLabel>
                  <Input
                    id="name"
                    name="name"
                    value="支付测试"
                    disabled
                    readOnly
                  />
                </Field>
              </FieldGroup>
              <Field>
                <FieldLabel htmlFor="money">付款金额</FieldLabel>
                <div className="relative">
                  <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground">
                    ¥
                  </span>
                  <Input
                    id="money"
                    name="money"
                    className="pl-8"
                    defaultValue={String(config.money ?? "1")}
                    disabled={config.paid}
                    readOnly={config.paid}
                    required
                  />
                </div>
              </Field>
              {config.paid ? (
                <Alert className="rounded-xl border-primary/30 bg-primary/5">
                  <CheckCircle2 className="text-primary" />
                  <AlertDescription className="text-primary">
                    订单已支付成功！
                  </AlertDescription>
                </Alert>
              ) : (
                <>
                  {config.captcha && (
                    <Field>
                      <FieldLabel>安全验证</FieldLabel>
                      <div
                        id="captcha"
                        className="rounded-xl border bg-muted/30 p-3"
                      >
                        <p
                          id="captcha_text"
                          className="text-center text-sm text-muted-foreground"
                        >
                          正在加载验证码
                        </p>
                        <div
                          id="captcha_wait"
                          className="hidden text-center text-sm text-muted-foreground"
                        >
                          验证加载中…
                        </div>
                      </div>
                    </Field>
                  )}
                  <div className="grid gap-3 sm:grid-cols-2">
                    {(config.paytype ?? []).map((type) => (
                      <Button
                        key={String(type.id)}
                        type="button"
                        name="type"
                        value={String(type.id)}
                        variant="outline"
                        className="h-12 justify-start rounded-xl"
                        onClick={(event) => submit(event.currentTarget)}
                      >
                        <img
                          src={`/assets/icon/${type.name ?? ""}.ico`}
                          alt=""
                          className="mr-2 size-5 rounded"
                        />
                        {type.showname ?? "支付方式"}
                      </Button>
                    ))}
                  </div>
                </>
              )}
            </form>
          </CardContent>
          <CardFooter className="justify-between border-t text-xs text-muted-foreground">
            <span>
              {config.sitename ?? "Rainbow Pay"} · © {new Date().getFullYear()}
            </span>
            <span>仅用于接口联调</span>
          </CardFooter>
        </Card>
      </div>
    </div>
  )
}
