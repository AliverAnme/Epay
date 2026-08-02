import * as React from "react"
import {
  Activity,
  ArrowUpRight,
  BarChart3,
  Check,
  ChevronRight,
  CircleDollarSign,
  CreditCard,
  FileText,
  LayoutDashboard,
  LifeBuoy,
  Loader2,
  LogOut,
  Menu,
  Moon,
  PackageCheck,
  PanelLeft,
  RefreshCw,
  Settings,
  ShieldCheck,
  Store,
  Sun,
  Users,
  WalletCards,
} from "lucide-react"

import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { AuthView } from "@/components/epay/auth-view"
import { PublicHomeView } from "@/components/epay/public-home"
import {
  TransferConfirmView,
  type TransferConfirmConfig,
} from "@/components/epay/transfer-confirm"
import {
  PaymentStatusView,
  type PaymentStatusConfig,
} from "@/components/epay/payment-status"
import { PayPageView, type PayPageConfig } from "@/components/epay/pay-page"
import {
  TestPaymentView,
  type TestPaymentConfig,
} from "@/components/epay/test-payment"
import { Avatar, AvatarFallback } from "@/components/ui/avatar"
import { Badge } from "@/components/ui/badge"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { ScrollArea } from "@/components/ui/scroll-area"
import { Separator } from "@/components/ui/separator"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { useTheme } from "@/components/theme-provider"
import { cn } from "@/lib/utils"

type EpayView =
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
type JsonObject = Record<string, unknown>

type CashierConfig = {
  tradeNo?: string
  sitename?: string
  other?: boolean
  order?: {
    name?: string
    addtime?: string
    money?: string
    realmoney?: string
  }
  paytype?: Array<{ id: string | number; name: string; showname: string }>
}

type EpayAppProps = {
  view: EpayView
  config?: JsonObject | CashierConfig
}

type NavItem = { label: string; href: string; icon: React.ElementType }

const adminNav: NavItem[] = [
  { label: "平台首页", href: "./", icon: LayoutDashboard },
  { label: "收款订单", href: "./order.php", icon: FileText },
  { label: "付款管理", href: "./transfer.php", icon: WalletCards },
  { label: "商户管理", href: "./ulist.php", icon: Users },
  { label: "支付接口", href: "./pay_channel.php", icon: CreditCard },
  { label: "系统设置", href: "./set.php?mod=site", icon: Settings },
]

const merchantNav: NavItem[] = [
  { label: "用户中心", href: "./", icon: LayoutDashboard },
  { label: "订单记录", href: "order.php", icon: FileText },
  { label: "结算记录", href: "settle.php", icon: PackageCheck },
  { label: "资金明细", href: "record.php", icon: BarChart3 },
  { label: "申请提现", href: "apply.php", icon: ArrowUpRight },
  { label: "个人资料", href: "userinfo.php?mod=api", icon: Store },
]

function valueOf(
  data: JsonObject | null | undefined,
  key: string,
  fallback = "0"
) {
  const value = data?.[key]
  return value === null || value === undefined || value === ""
    ? fallback
    : String(value)
}

function objectOf(
  data: JsonObject | null | undefined,
  key: string
): JsonObject {
  const value = data?.[key]
  return value && typeof value === "object" ? (value as JsonObject) : {}
}

function Brand({ compact = false }: { compact?: boolean }) {
  return (
    <div className="flex items-center gap-3">
      <div className="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
        <CircleDollarSign className="size-5" aria-hidden="true" />
      </div>
      {!compact && (
        <div className="leading-tight">
          <p className="font-semibold tracking-tight">Rainbow Pay</p>
          <p className="text-[11px] text-muted-foreground">支付运营工作台</p>
        </div>
      )}
    </div>
  )
}

function NavLinks({
  items,
  onNavigate,
}: {
  items: NavItem[]
  onNavigate?: () => void
}) {
  return (
    <nav className="grid gap-1" aria-label="主导航">
      {items.map(({ label, href, icon: Icon }, index) => (
        <Button
          key={label}
          asChild
          variant={index === 0 ? "secondary" : "ghost"}
          className={cn(
            "h-10 justify-start gap-3 rounded-xl px-3 font-normal",
            index === 0 && "font-medium"
          )}
        >
          <a href={href} onClick={onNavigate}>
            <Icon className="size-4 text-muted-foreground" aria-hidden="true" />
            <span>{label}</span>
            {index === 0 && (
              <ChevronRight
                className="ml-auto size-4 text-muted-foreground"
                aria-hidden="true"
              />
            )}
          </a>
        </Button>
      ))}
    </nav>
  )
}

function WorkspaceShell({
  children,
  kind,
  title,
  description,
}: {
  children: React.ReactNode
  kind: "admin" | "merchant"
  title: string
  description: string
}) {
  const { theme, setTheme } = useTheme()
  const dark = theme === "dark"
  const [mobileOpen, setMobileOpen] = React.useState(false)
  const nav = kind === "admin" ? adminNav : merchantNav
  return (
    <div className="min-h-svh bg-muted/30 text-foreground antialiased">
      <header className="sticky top-0 z-40 border-b bg-background/90 backdrop-blur-xl">
        <div className="mx-auto flex h-16 max-w-[1440px] items-center gap-3 px-4 sm:px-6 lg:px-8">
          <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
            <SheetTrigger asChild>
              <Button
                variant="ghost"
                size="icon"
                className="md:hidden"
                aria-label="打开导航"
              >
                <Menu className="size-5" />
              </Button>
            </SheetTrigger>
            <SheetContent side="left" className="w-[280px] p-0">
              <SheetHeader className="border-b px-5 py-4 text-left">
                <SheetTitle>
                  <Brand />
                </SheetTitle>
                <SheetDescription>快速访问常用功能</SheetDescription>
              </SheetHeader>
              <ScrollArea className="h-[calc(100vh-118px)] px-4 py-5">
                <NavLinks items={nav} onNavigate={() => setMobileOpen(false)} />
              </ScrollArea>
            </SheetContent>
          </Sheet>
          <div className="hidden md:block">
            <Brand />
          </div>
          <Separator
            orientation="vertical"
            className="mx-2 hidden h-6 md:block"
          />
          <div className="min-w-0">
            <p className="truncate text-sm font-medium">{title}</p>
            <p className="hidden truncate text-xs text-muted-foreground sm:block">
              {description}
            </p>
          </div>
          <div className="ml-auto flex items-center gap-1.5">
            <Button
              variant="ghost"
              size="icon"
              className="rounded-xl"
              onClick={() => setTheme(dark ? "light" : "dark")}
              aria-label={dark ? "切换亮色模式" : "切换暗色模式"}
            >
              {dark ? <Sun className="size-4" /> : <Moon className="size-4" />}
            </Button>
            <Button
              variant="ghost"
              size="icon"
              className="hidden rounded-xl sm:inline-flex"
              aria-label="帮助中心"
            >
              <LifeBuoy className="size-4" />
            </Button>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-10 gap-2 rounded-xl px-2">
                  <Avatar className="size-7">
                    <AvatarFallback>
                      {kind === "admin" ? "AD" : "商户"}
                    </AvatarFallback>
                  </Avatar>
                  <span className="hidden text-sm sm:inline">
                    {kind === "admin" ? "管理员" : "商户账户"}
                  </span>
                  <span className="text-muted-foreground">···</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuItem asChild>
                  <a
                    href={
                      kind === "admin"
                        ? "./set.php?mod=account"
                        : "editinfo.php"
                    }
                  >
                    <Settings className="mr-2 size-4" />
                    账户设置
                  </a>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <a href="/doc.html" target="_blank" rel="noreferrer">
                    <FileText className="mr-2 size-4" />
                    开发文档
                  </a>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                  <a
                    href={
                      kind === "admin"
                        ? "./login.php?logout"
                        : "login.php?logout"
                    }
                  >
                    <LogOut className="mr-2 size-4" />
                    退出登录
                  </a>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </header>
      <div className="mx-auto flex max-w-[1440px]">
        <aside className="sticky top-16 hidden h-[calc(100vh-4rem)] w-64 shrink-0 border-r bg-background/60 p-4 md:block">
          <div className="mb-6 rounded-2xl border bg-card p-4 shadow-sm">
            <p className="text-xs font-medium tracking-wide text-muted-foreground">
              当前工作区
            </p>
            <p className="mt-1 font-semibold">
              {kind === "admin" ? "平台运营" : "商户管理"}
            </p>
            <Badge
              variant="secondary"
              className="mt-3 gap-1.5 rounded-lg font-normal"
            >
              <span className="size-1.5 rounded-full bg-emerald-500" />
              运行正常
            </Badge>
          </div>
          <NavLinks items={nav} />
          <div className="mt-auto pt-8">
            <Separator className="mb-4" />
            <div className="flex items-center gap-2 px-2 text-xs text-muted-foreground">
              <PanelLeft className="size-3.5" />
              快捷导航已启用
            </div>
          </div>
        </aside>
        <main className="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">{children}</main>
      </div>
    </div>
  )
}

function PageHeading({
  eyebrow,
  title,
  description,
  action,
}: {
  eyebrow: string
  title: string
  description: string
  action?: React.ReactNode
}) {
  return (
    <div className="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div className="min-w-0">
        <Breadcrumb className="mb-3">
          <BreadcrumbList>
            <BreadcrumbItem>
              <BreadcrumbLink href="/">Rainbow Pay</BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbPage>{eyebrow}</BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
          {title}
        </h1>
        <p className="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">
          {description}
        </p>
      </div>
      {action}
    </div>
  )
}

function LegacyContentSlot() {
  return (
    <Card className="epay-legacy-card rounded-2xl shadow-sm">
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

function StatCard({
  label,
  value,
  hint,
  icon: Icon,
  tone = "blue",
  loading = false,
}: {
  label: string
  value: string
  hint: string
  icon: React.ElementType
  tone?: "blue" | "green" | "amber" | "violet"
  loading?: boolean
}) {
  const toneClass = {
    blue: "bg-blue-500/10 text-blue-600",
    green: "bg-emerald-500/10 text-emerald-600",
    amber: "bg-amber-500/10 text-amber-600",
    violet: "bg-violet-500/10 text-violet-600",
  }[tone]
  return (
    <Card className="group overflow-hidden rounded-2xl border bg-card shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
      <CardContent className="p-5">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <p className="text-sm text-muted-foreground">{label}</p>
            {loading ? (
              <Skeleton className="mt-3 h-8 w-28" />
            ) : (
              <p className="mt-2 truncate text-2xl font-semibold tracking-tight">
                {value}
              </p>
            )}
            <p className="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
              <ArrowUpRight className="size-3 text-emerald-500" />
              {hint}
            </p>
          </div>
          <div
            className={cn(
              "flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-110",
              toneClass
            )}
          >
            <Icon className="size-5" />
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

function LoadingState() {
  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <Skeleton className="h-32 rounded-2xl" />
      <Skeleton className="h-32 rounded-2xl" />
      <Skeleton className="h-32 rounded-2xl" />
      <Skeleton className="h-32 rounded-2xl" />
    </div>
  )
}

function FetchError({ onRetry }: { onRetry: () => void }) {
  return (
    <Alert variant="destructive" className="rounded-2xl">
      <AlertTitle>数据暂时无法加载</AlertTitle>
      <AlertDescription className="flex flex-wrap items-center gap-3">
        请检查登录状态或稍后重试。
        <Button variant="outline" size="sm" onClick={onRetry}>
          <RefreshCw className="mr-1.5 size-3.5" />
          重新加载
        </Button>
      </AlertDescription>
    </Alert>
  )
}

function AdminDashboard() {
  const [data, setData] = React.useState<JsonObject | null>(null)
  const [loading, setLoading] = React.useState(true)
  const [failed, setFailed] = React.useState(false)
  const load = React.useCallback(() => {
    setLoading(true)
    setFailed(false)
    fetch("ajax.php?act=getcount", { credentials: "same-origin" })
      .then((response) => {
        if (!response.ok) throw new Error("request failed")
        return response.json() as Promise<JsonObject>
      })
      .then(setData)
      .catch(() => setFailed(true))
      .finally(() => setLoading(false))
  }, [])
  React.useEffect(() => {
    const timer = window.setTimeout(load, 0)
    return () => window.clearTimeout(timer)
  }, [load])
  const order = objectOf(data, "order")
  const orderToday = objectOf(data, "order_today")
  const rows = Object.entries(order).slice(0, 7)
  return (
    <WorkspaceShell
      kind="admin"
      title="平台运营"
      description="统一管理订单、商户、支付通道与结算"
    >
      <PageHeading
        eyebrow="平台首页"
        title="运营总览"
        description="实时掌握支付业务的核心指标与近期趋势。"
        action={
          <Button onClick={load} variant="outline" className="rounded-xl">
            <RefreshCw className="mr-2 size-4" />
            刷新数据
          </Button>
        }
      />
      {failed ? (
        <FetchError onRetry={load} />
      ) : loading && !data ? (
        <LoadingState />
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
              label="订单总数"
              value={valueOf(data, "count1")}
              hint="较昨日持续更新"
              icon={FileText}
              tone="blue"
              loading={loading}
            />
            <StatCard
              label="商户数量"
              value={valueOf(data, "count2")}
              hint="活跃商户总数"
              icon={Users}
              tone="violet"
              loading={loading}
            />
            <StatCard
              label="平台总余额"
              value={`¥ ${valueOf(data, "usermoney")}`}
              hint="每小时同步一次"
              icon={CircleDollarSign}
              tone="green"
              loading={loading}
            />
            <StatCard
              label="今日成功率"
              value={`${valueOf(data, "success_rate")}%`}
              hint="今日订单统计"
              icon={Activity}
              tone="amber"
              loading={loading}
            />
          </div>
          <div className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,.6fr)]">
            <Card className="rounded-2xl shadow-sm">
              <CardHeader className="flex flex-row items-start justify-between gap-3 space-y-0">
                <div>
                  <CardTitle className="text-base">近期开单趋势</CardTitle>
                  <CardDescription>按日期汇总的订单金额与笔数</CardDescription>
                </div>
                <Badge variant="outline" className="rounded-lg font-normal">
                  自动更新
                </Badge>
              </CardHeader>
              <CardContent>
                <Tabs defaultValue="amount">
                  <TabsList className="mb-4 rounded-xl">
                    <TabsTrigger value="amount">订单金额</TabsTrigger>
                    <TabsTrigger value="count">订单数量</TabsTrigger>
                  </TabsList>
                  <TabsContent value="amount" className="mt-0">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>日期</TableHead>
                          <TableHead className="text-right">订单金额</TableHead>
                          <TableHead className="text-right">订单数</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {rows.length ? (
                          rows.map(([date, row]) => (
                            <TableRow key={date}>
                              <TableCell className="font-medium">
                                {date}
                              </TableCell>
                              <TableCell className="text-right">
                                ¥ {valueOf(row as JsonObject, "all")}
                              </TableCell>
                              <TableCell className="text-right">
                                {valueOf(row as JsonObject, "count", "—")}
                              </TableCell>
                            </TableRow>
                          ))
                        ) : (
                          <TableRow>
                            <TableCell
                              colSpan={3}
                              className="h-24 text-center text-muted-foreground"
                            >
                              暂无趋势数据
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TabsContent>
                  <TabsContent value="count" className="mt-0">
                    <div className="grid gap-3 sm:grid-cols-2">
                      {Object.entries(orderToday)
                        .slice(0, 6)
                        .map(([key, value]) => (
                          <div
                            key={key}
                            className="rounded-xl border bg-muted/30 p-4"
                          >
                            <p className="text-xs text-muted-foreground">
                              {key}
                            </p>
                            <p className="mt-2 text-xl font-semibold">
                              {String(value)}
                            </p>
                          </div>
                        ))}
                    </div>
                  </TabsContent>
                </Tabs>
              </CardContent>
            </Card>
            <Card className="rounded-2xl shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">快捷入口</CardTitle>
                <CardDescription>高频运营动作</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-2">
                <Button
                  asChild
                  variant="outline"
                  className="h-11 justify-between rounded-xl"
                >
                  <a href="./order.php">
                    <span className="flex items-center gap-2">
                      <FileText className="size-4 text-blue-500" />
                      订单管理
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
                <Button
                  asChild
                  variant="outline"
                  className="h-11 justify-between rounded-xl"
                >
                  <a href="./ulist.php">
                    <span className="flex items-center gap-2">
                      <Users className="size-4 text-violet-500" />
                      商户列表
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
                <Button
                  asChild
                  variant="outline"
                  className="h-11 justify-between rounded-xl"
                >
                  <a href="./pay_channel.php">
                    <span className="flex items-center gap-2">
                      <CreditCard className="size-4 text-emerald-500" />
                      支付通道
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
              </CardContent>
              <CardFooter>
                <p className="text-xs text-muted-foreground">
                  平台时间：{new Date().toLocaleString("zh-CN")}
                </p>
              </CardFooter>
            </Card>
          </div>
        </>
      )}
    </WorkspaceShell>
  )
}

function MerchantDashboard({ config }: { config?: JsonObject }) {
  const [data, setData] = React.useState<JsonObject | null>(null)
  const [loading, setLoading] = React.useState(true)
  const [failed, setFailed] = React.useState(false)
  const load = React.useCallback(() => {
    setLoading(true)
    setFailed(false)
    fetch("ajax2.php?act=getcount", { credentials: "same-origin" })
      .then((response) => {
        if (!response.ok) throw new Error("request failed")
        return response.json() as Promise<JsonObject>
      })
      .then((count) => setData({ ...count, money: config?.money ?? "0.00" }))
      .catch(() => setFailed(true))
      .finally(() => setLoading(false))
  }, [config?.money])
  React.useEffect(() => {
    const timer = window.setTimeout(load, 0)
    return () => window.clearTimeout(timer)
  }, [load])
  const channels = Array.isArray(data?.channels)
    ? (data?.channels as JsonObject[])
    : []
  return (
    <WorkspaceShell
      kind="merchant"
      title="商户工作台"
      description="收款、结算与接口配置一站式管理"
    >
      <PageHeading
        eyebrow="用户中心"
        title="欢迎回来"
        description="这是你的商户经营概览，重要状态会在这里第一时间提醒。"
        action={
          <div className="flex gap-2">
            <Button asChild variant="outline" className="rounded-xl">
              <a href="userinfo.php?mod=api">
                <ShieldCheck className="mr-2 size-4" />
                API 信息
              </a>
            </Button>
            <Button asChild className="rounded-xl">
              <a href="order.php">查看订单</a>
            </Button>
          </div>
        }
      />
      {failed ? (
        <FetchError onRetry={load} />
      ) : loading && !data ? (
        <LoadingState />
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
              label="账户余额"
              value={`¥ ${valueOf(data, "money", "0.00")}`}
              hint="可用于平台代收"
              icon={CircleDollarSign}
              tone="blue"
              loading={loading}
            />
            <StatCard
              label="已结算余额"
              value={`¥ ${valueOf(data, "settle_money", "0.00")}`}
              hint="累计结算金额"
              icon={WalletCards}
              tone="green"
              loading={loading}
            />
            <StatCard
              label="订单总数"
              value={valueOf(data, "orders")}
              hint="历史累计"
              icon={FileText}
              tone="violet"
              loading={loading}
            />
            <StatCard
              label="今日订单"
              value={valueOf(data, "orders_today")}
              hint="今日实时统计"
              icon={Activity}
              tone="amber"
              loading={loading}
            />
          </div>
          <div className="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)]">
            <Card className="rounded-2xl shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">支付渠道表现</CardTitle>
                <CardDescription>各渠道今日收入、成功率与费率</CardDescription>
              </CardHeader>
              <CardContent>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>渠道</TableHead>
                      <TableHead>今日收入</TableHead>
                      <TableHead>成功率</TableHead>
                      <TableHead className="text-right">费率</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {channels.length ? (
                      channels.map((channel) => (
                        <TableRow key={String(channel.name)}>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <div className="flex size-8 items-center justify-center rounded-lg bg-muted">
                                <CreditCard className="size-4 text-muted-foreground" />
                              </div>
                              <span className="font-medium">
                                {String(
                                  channel.showname ?? channel.name ?? "渠道"
                                )}
                              </span>
                            </div>
                          </TableCell>
                          <TableCell>
                            ¥ {String(channel.order_today ?? "0")}
                          </TableCell>
                          <TableCell>
                            <Badge
                              variant="secondary"
                              className="rounded-lg font-normal"
                            >
                              {String(channel.success_rate ?? "0")} %
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right">
                            {String(channel.rate ?? "0")} %
                          </TableCell>
                        </TableRow>
                      ))
                    ) : (
                      <TableRow>
                        <TableCell
                          colSpan={4}
                          className="h-24 text-center text-muted-foreground"
                        >
                          暂无渠道数据
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
            <Card className="rounded-2xl shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">账户状态</CardTitle>
                <CardDescription>保持资料完整，收款更顺畅</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex items-center gap-3 rounded-xl border bg-emerald-500/5 p-3">
                  <div className="flex size-9 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                    <Check className="size-4" />
                  </div>
                  <div>
                    <p className="text-sm font-medium">商户服务正常</p>
                    <p className="text-xs text-muted-foreground">
                      收款与结算功能均已开启
                    </p>
                  </div>
                </div>
                <Button
                  asChild
                  variant="outline"
                  className="h-11 w-full justify-between rounded-xl"
                >
                  <a href="editinfo.php">
                    <span className="flex items-center gap-2">
                      <Settings className="size-4" />
                      完善商户资料
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
                <Button
                  asChild
                  variant="outline"
                  className="h-11 w-full justify-between rounded-xl"
                >
                  <a href="settle.php">
                    <span className="flex items-center gap-2">
                      <PackageCheck className="size-4" />
                      查看结算记录
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </WorkspaceShell>
  )
}

function CashierView({ config }: { config?: CashierConfig }) {
  const order = config?.order ?? {}
  const types = config?.paytype ?? []
  const [selected, setSelected] = React.useState(String(types[0]?.id ?? ""))
  const [submitting, setSubmitting] = React.useState(false)
  const [error, setError] = React.useState("")
  const submit = () => {
    if (!selected) {
      setError("请选择一种支付方式")
      return
    }
    setSubmitting(true)
    setError("")
    window.location.href = `/submit2.php?typeid=${encodeURIComponent(selected)}&trade_no=${encodeURIComponent(config?.tradeNo ?? "")}`
  }
  return (
    <div className="min-h-svh bg-muted/30 px-4 py-6 text-foreground sm:px-6 lg:px-8">
      <div className="mx-auto max-w-3xl">
        <header className="mb-8 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Brand />
            <Separator orientation="vertical" className="h-6" />
            <Badge variant="secondary" className="rounded-lg font-normal">
              安全收银台
            </Badge>
          </div>
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <ShieldCheck className="size-4 text-emerald-500" />
            支付过程已加密
          </div>
        </header>
        {config?.other ? (
          <Alert className="mb-5 rounded-2xl">
            <AlertTitle>当前支付方式暂时维护</AlertTitle>
            <AlertDescription>请返回并选择其他可用支付方式。</AlertDescription>
          </Alert>
        ) : (
          <Card className="rounded-2xl shadow-sm">
            <CardHeader className="border-b">
              <CardTitle className="text-lg">确认订单</CardTitle>
              <CardDescription>请核对订单信息后选择支付方式</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-5 p-5 sm:grid-cols-[1fr_auto] sm:p-7">
              <div className="space-y-3 text-sm">
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">商品名称</span>
                  <span className="max-w-[230px] truncate font-medium">
                    {order.name || "—"}
                  </span>
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">订单号</span>
                  <span className="max-w-[230px] truncate font-mono text-xs">
                    {config?.tradeNo || "—"}
                  </span>
                </div>
                <div className="flex justify-between gap-4">
                  <span className="text-muted-foreground">创建时间</span>
                  <span>{order.addtime || "—"}</span>
                </div>
              </div>
              <div className="rounded-2xl bg-primary/5 px-5 py-4 text-right">
                <p className="text-xs text-muted-foreground">需支付金额</p>
                <p className="mt-1 text-3xl font-semibold tracking-tight text-primary">
                  ¥ {order.realmoney || order.money || "0.00"}
                </p>
              </div>
            </CardContent>
          </Card>
        )}
        {!config?.other && (
          <>
            <Card className="mt-5 rounded-2xl shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">选择支付方式</CardTitle>
                <CardDescription>
                  选择一种方式完成付款，支付过程由对应平台安全处理。
                </CardDescription>
              </CardHeader>
              <CardContent className="grid gap-3 p-5 sm:grid-cols-2 sm:p-7">
                {types.map((type) => (
                  <button
                    type="button"
                    key={String(type.id)}
                    aria-pressed={selected === String(type.id)}
                    onClick={() => setSelected(String(type.id))}
                    className={cn(
                      "group flex items-center gap-3 rounded-xl border bg-background p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-sm",
                      selected === String(type.id) &&
                        "border-primary bg-primary/5 ring-2 ring-primary/15"
                    )}
                  >
                    <div className="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground transition-colors group-hover:bg-primary/10 group-hover:text-primary">
                      <CreditCard className="size-5" />
                    </div>
                    <span className="flex-1 text-sm font-medium">
                      {type.showname}
                    </span>
                    {selected === String(type.id) && (
                      <Check className="size-4 text-primary" />
                    )}
                  </button>
                ))}
              </CardContent>
            </Card>
            <div className="mt-5 flex flex-col items-stretch gap-3 rounded-2xl border bg-background p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
              <p className="flex items-center gap-2 text-xs text-muted-foreground">
                <ShieldCheck className="size-4 text-emerald-500" />
                支付前请确认订单信息
              </p>
              <div className="flex items-center gap-4">
                <span className="text-sm text-muted-foreground">
                  支付{" "}
                  <strong className="text-lg text-primary">
                    ¥ {order.realmoney || order.money || "0.00"}
                  </strong>
                </span>
                <Button
                  onClick={submit}
                  disabled={submitting}
                  className="h-11 rounded-xl px-6"
                >
                  {submitting ? (
                    <Loader2 className="mr-2 size-4 animate-spin" />
                  ) : null}
                  {submitting ? "正在跳转" : "立即支付"}
                </Button>
              </div>
            </div>
            {error && (
              <p className="mt-3 text-center text-sm text-destructive">
                {error}
              </p>
            )}
          </>
        )}
      </div>
    </div>
  )
}

function PaymentView() {
  return (
    <div className="flex min-h-svh items-center justify-center bg-muted/30 p-6">
      <Card className="w-full max-w-md rounded-2xl shadow-sm">
        <CardHeader>
          <CardTitle>支付确认</CardTitle>
          <CardDescription>请使用收银台完成支付。</CardDescription>
        </CardHeader>
        <CardContent>
          <Button asChild className="w-full rounded-xl">
            <a href="/">返回首页</a>
          </Button>
        </CardContent>
      </Card>
    </div>
  )
}

export function EpayApp({ view, config }: EpayAppProps) {
  const shellConfig =
    config && typeof config === "object" ? (config as JsonObject) : {}
  const shellTitle = String(shellConfig.title ?? "")
  if (
    view === "admin-login" ||
    view === "user-login" ||
    view === "user-register" ||
    view === "user-recovery"
  )
    return <AuthView mode={view} config={config as JsonObject | undefined} />
  if (view === "public-home")
    return <PublicHomeView config={config as JsonObject | undefined} />
  if (view === "test-payment")
    return <TestPaymentView config={config as TestPaymentConfig | undefined} />
  if (view === "payment-status")
    return (
      <PaymentStatusView config={config as PaymentStatusConfig | undefined} />
    )
  if (view === "transfer-confirm")
    return (
      <TransferConfirmView
        config={config as TransferConfirmConfig | undefined}
      />
    )
  if (view === "pay-page")
    return <PayPageView config={config as PayPageConfig | undefined} />
  if (view === "merchant-dashboard")
    return <MerchantDashboard config={config as JsonObject | undefined} />
  if (view === "merchant-shell")
    return (
      <WorkspaceShell
        kind="merchant"
        title={shellTitle || "商户工作台"}
        description="收款、结算与接口配置一站式管理"
      >
        <LegacyContentSlot />
      </WorkspaceShell>
    )
  if (view === "cashier")
    return <CashierView config={config as CashierConfig | undefined} />
  if (view === "payment") return <PaymentView />
  if (view === "admin-shell")
    return (
      <WorkspaceShell
        kind="admin"
        title={shellTitle || "平台运营"}
        description="统一管理订单、商户、支付通道与结算"
      >
        <LegacyContentSlot />
      </WorkspaceShell>
    )
  return <AdminDashboard />
}
