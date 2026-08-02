import * as React from "react"
import {
  ArrowLeft,
  BookOpen,
  CircleDollarSign,
  ExternalLink,
  QrCode,
  ShieldCheck,
} from "lucide-react"

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
import { cn } from "@/lib/utils"

export type LegacyShellConfig = {
  title?: string
  description?: string
  sitename?: string
  kind?: string
  step?: number | string
  page?: string
  amount?: string | number
}

function LegacyContentSlot({ className }: { className?: string }) {
  return (
    <Card className={cn("epay-legacy-card rounded-2xl shadow-sm", className)}>
      <CardHeader className="sr-only">
        <CardTitle>页面内容</CardTitle>
      </CardHeader>
      <CardContent className="p-0">
        <div
          id="epay-react-legacy-slot"
          className="min-w-0"
          aria-live="polite"
        />
      </CardContent>
    </Card>
  )
}

function ShellBrand({ sitename }: { sitename: string }) {
  return (
    <div className="flex min-w-0 items-center gap-3">
      <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
        <CircleDollarSign className="size-5" aria-hidden="true" />
      </div>
      <div className="min-w-0 leading-tight">
        <p className="truncate font-semibold tracking-tight">{sitename}</p>
        <p className="truncate text-[11px] text-muted-foreground">
          统一支付服务
        </p>
      </div>
    </div>
  )
}

const authMeta: Record<
  string,
  { label: string; description: string; icon: React.ElementType }
> = {
  wx: {
    label: "微信扫码登录",
    description: "使用微信扫描页面中的二维码完成登录或账号绑定。",
    icon: QrCode,
  },
  qq: {
    label: "QQ扫码登录",
    description: "使用 QQ 手机版扫描二维码完成登录或账号绑定。",
    icon: QrCode,
  },
  alipay: {
    label: "支付宝快捷登录",
    description: "确认后将跳转到支付宝完成授权，页面会自动等待结果。",
    icon: ExternalLink,
  },
  certificate: {
    label: "实名认证",
    description: "正在打开实名认证服务，请保持当前页面处于打开状态。",
    icon: ShieldCheck,
  },
}

function LegacyShellHeader({
  sitename,
  title,
  description,
  icon: Icon,
}: {
  sitename: string
  title: string
  description: string
  icon: React.ElementType
}) {
  return (
    <CardHeader className="gap-4 border-b bg-muted/20 px-5 py-5 sm:px-7">
      <div className="flex items-center justify-between gap-4">
        <ShellBrand sitename={sitename} />
        <Badge variant="secondary" className="shrink-0 gap-1.5 rounded-lg">
          <Icon className="size-3.5" aria-hidden="true" />
          安全流程
        </Badge>
      </div>
      <div>
        <CardTitle className="text-xl tracking-tight sm:text-2xl">
          {title}
        </CardTitle>
        <CardDescription className="mt-1 max-w-xl leading-6">
          {description}
        </CardDescription>
      </div>
    </CardHeader>
  )
}

export function LegacyAuthShell({ config }: { config?: LegacyShellConfig }) {
  const kind = String(config?.kind ?? "wx")
  const meta = authMeta[kind] ?? authMeta.wx
  const sitename = config?.sitename || "Rainbow Pay"
  const title = config?.title || meta.label
  const description = config?.description || meta.description

  return (
    <main className="flex min-h-svh items-center justify-center bg-muted/30 p-4 text-foreground sm:p-8">
      <Card className="w-full max-w-2xl overflow-hidden rounded-3xl border bg-card/95 shadow-xl shadow-primary/5 backdrop-blur">
        <LegacyShellHeader
          sitename={sitename}
          title={title}
          description={description}
          icon={meta.icon}
        />
        <CardContent className="p-3 sm:p-5">
          <LegacyContentSlot className="shadow-none" />
        </CardContent>
        <CardFooter className="flex flex-col items-stretch gap-3 border-t bg-muted/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7">
          <p className="flex items-center gap-2 text-xs text-muted-foreground">
            <ShieldCheck className="size-4 text-primary" aria-hidden="true" />
            请在官方页面完成授权
          </p>
          <Button asChild variant="outline" className="rounded-xl">
            <a href="./login.php">
              <ArrowLeft data-icon="inline-start" />
              返回登录
            </a>
          </Button>
        </CardFooter>
      </Card>
    </main>
  )
}

export function GatewayShell({ config }: { config?: LegacyShellConfig }) {
  const sitename = config?.sitename || "Rainbow Pay"
  const title = config?.title || "支付页面"
  const description =
    config?.description ||
    "请按照页面提示完成支付，支付状态会由原支付通道实时回传。"

  return (
    <main className="min-h-svh bg-muted/30 px-3 py-4 text-foreground sm:px-6 sm:py-8">
      <div className="mx-auto w-full max-w-3xl">
        <div className="mb-4 flex items-center justify-between gap-3 px-1 sm:mb-5 sm:px-2">
          <ShellBrand sitename={sitename} />
          <Badge variant="secondary" className="gap-1.5 rounded-lg">
            <ShieldCheck className="size-3.5" aria-hidden="true" />
            安全支付
          </Badge>
        </div>
        <Card className="overflow-hidden rounded-3xl border bg-card/95 shadow-xl shadow-primary/5 backdrop-blur">
          <CardHeader className="gap-2 border-b bg-muted/20 px-5 py-5 sm:px-7">
            <div className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
              <BookOpen className="size-3.5" aria-hidden="true" />
              支付收银台
            </div>
            <CardTitle className="text-xl tracking-tight sm:text-2xl">
              {title}
            </CardTitle>
            <CardDescription className="leading-6">
              {description}
            </CardDescription>
          </CardHeader>
          <CardContent className="p-3 sm:p-5">
            <LegacyContentSlot className="shadow-none" />
          </CardContent>
          <CardFooter className="border-t bg-muted/10 px-5 py-4 sm:px-7">
            <p className="flex items-center gap-2 text-xs text-muted-foreground">
              <ShieldCheck className="size-4 text-primary" aria-hidden="true" />
              请勿关闭或刷新支付页面
            </p>
          </CardFooter>
        </Card>
      </div>
    </main>
  )
}

export function PublicLegacyShell({ config }: { config?: LegacyShellConfig }) {
  const sitename = config?.sitename || "Rainbow Pay"
  const title = config?.title || "网站页面"
  const description =
    config?.description || "欢迎访问 Rainbow Pay，以下内容由站点服务提供。"

  return (
    <main className="min-h-svh bg-muted/30 px-3 py-4 text-foreground sm:px-6 sm:py-8">
      <div className="mx-auto w-full max-w-5xl">
        <div className="mb-4 flex items-center justify-between gap-3 px-1 sm:mb-5 sm:px-2">
          <ShellBrand sitename={sitename} />
          <Badge variant="outline" className="rounded-lg">
            官网
          </Badge>
        </div>
        <Card className="overflow-hidden rounded-3xl border bg-card/95 shadow-xl shadow-primary/5 backdrop-blur">
          <CardHeader className="gap-2 border-b bg-muted/20 px-5 py-5 sm:px-7">
            <CardTitle className="text-xl tracking-tight sm:text-2xl">
              {title}
            </CardTitle>
            <CardDescription className="leading-6">
              {description}
            </CardDescription>
          </CardHeader>
          <CardContent className="p-3 sm:p-5">
            <LegacyContentSlot className="shadow-none" />
          </CardContent>
          <CardFooter className="flex items-center justify-between gap-3 border-t bg-muted/10 px-5 py-4 sm:px-7">
            <p className="text-xs text-muted-foreground">{sitename}</p>
            <Button asChild variant="outline" className="rounded-xl">
              <a href="/">
                <ArrowLeft data-icon="inline-start" />
                返回首页
              </a>
            </Button>
          </CardFooter>
        </Card>
      </div>
    </main>
  )
}

export function InstallerShell({ config }: { config?: LegacyShellConfig }) {
  const sitename = config?.sitename || "彩虹易支付"
  const step = Math.max(1, Math.min(5, Number(config?.step || 1)))

  return (
    <main className="min-h-svh bg-muted/30 px-3 py-4 text-foreground sm:px-6 sm:py-10">
      <div className="mx-auto w-full max-w-3xl">
        <div className="mb-5 flex items-center justify-between gap-3 px-1 sm:px-2">
          <ShellBrand sitename={sitename} />
          <Badge variant="outline" className="rounded-lg">
            安装向导
          </Badge>
        </div>
        <Card className="overflow-hidden rounded-3xl border bg-card/95 shadow-xl shadow-primary/5 backdrop-blur">
          <CardHeader className="gap-4 border-b bg-muted/20 px-5 py-5 sm:px-7">
            <div>
              <CardTitle className="text-xl tracking-tight sm:text-2xl">
                欢迎安装彩虹易支付
              </CardTitle>
              <CardDescription className="mt-1 leading-6">
                按照向导完成环境检测、数据库配置和初始化。
              </CardDescription>
            </div>
            <div className="grid grid-cols-5 gap-1.5" aria-label="安装进度">
              {Array.from({ length: 5 }, (_, index) => {
                const current = index + 1
                return (
                  <div key={current} className="grid gap-1.5">
                    <div
                      className={cn(
                        "h-1.5 rounded-full bg-muted",
                        current <= step && "bg-primary"
                      )}
                    />
                    <span className="text-center text-[10px] text-muted-foreground">
                      {current}
                    </span>
                  </div>
                )
              })}
            </div>
          </CardHeader>
          <CardContent className="p-3 sm:p-5">
            <LegacyContentSlot className="shadow-none" />
          </CardContent>
          <CardFooter className="border-t bg-muted/10 px-5 py-4 sm:px-7">
            <p className="text-xs text-muted-foreground">
              安装过程中请勿重复提交或关闭页面。
            </p>
          </CardFooter>
        </Card>
        <Separator className="my-5" />
        <p className="text-center text-xs text-muted-foreground">
          Powered by {sitename}
        </p>
      </div>
    </main>
  )
}
