import * as React from "react"
import {
  Activity,
  ArrowUpRight,
  BarChart3,
  BookOpen,
  Check,
  ChevronRight,
  CircleDollarSign,
  CreditCard,
  FileText,
  LayoutDashboard,
  LifeBuoy,
  Loader2,
  LogOut,
  MessageCircle,
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
import { GoldPlanView, type GoldPlanConfig } from "@/components/epay/gold-plan"
import {
  GatewayShell,
  InstallerShell,
  LegacyAuthShell,
  PublicLegacyShell,
  type LegacyShellConfig,
} from "@/components/epay/legacy-shell"
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
  DropdownMenuGroup,
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

type NavItem = {
  label: string
  href: string
  icon: React.ElementType
  section?: string
  feature?: string
  external?: boolean
}

// 与 admin/head.php 中的旧版导航保持一一对应，避免新外壳吞掉原有入口。
const adminNav: NavItem[] = [
  { section: "概览", label: "平台首页", href: "./", icon: LayoutDashboard },
  {
    section: "收款订单",
    label: "订单管理",
    href: "./order.php",
    icon: FileText,
  },
  { label: "导出订单", href: "./export.php", icon: FileText },
  { label: "支付用户统计", href: "./buyerstat.php", icon: BarChart3 },
  { label: "黑名单管理", href: "./blacklist.php", icon: ShieldCheck },
  { label: "分账规则", href: "./ps_receiver.php", icon: Settings },
  { label: "分账记录", href: "./ps_order.php", icon: FileText },
  {
    section: "付款管理",
    label: "结算管理",
    href: "./slist.php",
    icon: PackageCheck,
  },
  { label: "批量结算", href: "./settle.php", icon: PackageCheck },
  { label: "付款记录", href: "./transfer.php", icon: WalletCards },
  { label: "新增付款", href: "./transfer_add.php", icon: ArrowUpRight },
  { label: "创建红包", href: "./transfer_red.php", icon: WalletCards },
  { label: "付款统计", href: "./transfer_stat.php", icon: BarChart3 },
  { label: "导出付款记录", href: "./transfer_export.php", icon: FileText },
  { label: "批量转账", href: "./transfer_batch.php", icon: ArrowUpRight },
  {
    label: "安全发转账记录",
    href: "./satf_transfer.php",
    icon: ShieldCheck,
    feature: "satf",
  },
  { section: "商户管理", label: "用户列表", href: "./ulist.php", icon: Users },
  { label: "用户组设置", href: "./glist.php", icon: Users },
  { label: "用户组购买", href: "./group.php", icon: Store },
  { label: "资金明细", href: "./record.php", icon: BarChart3 },
  { label: "支付统计", href: "./ustat.php", icon: Activity },
  {
    label: "授权域名",
    href: "./domain.php",
    icon: ShieldCheck,
    feature: "domain",
  },
  {
    label: "邀请码管理",
    href: "./invitecode.php",
    icon: ShieldCheck,
    feature: "invitecode",
  },
  {
    section: "支付接口",
    label: "支付通道",
    href: "./pay_channel.php",
    icon: CreditCard,
  },
  { label: "支付方式", href: "./pay_type.php", icon: CreditCard },
  { label: "支付插件", href: "./pay_plugin.php", icon: PackageCheck },
  { label: "支付通道轮询", href: "./pay_roll.php", icon: RefreshCw },
  { label: "公众号小程序", href: "./pay_weixin.php", icon: ShieldCheck },
  {
    label: "进件渠道管理",
    href: "./applyments_channel.php",
    icon: Store,
    feature: "applyments",
  },
  {
    label: "进件商户管理",
    href: "./applyments_merchant.php",
    icon: Store,
    feature: "applyments",
  },
  { label: "企业微信账号", href: "./pay_wework.php", icon: Store },
  {
    section: "系统设置",
    label: "网站信息配置",
    href: "./set.php?mod=site",
    icon: Settings,
  },
  { label: "支付相关配置", href: "./set.php?mod=pay", icon: CreditCard },
  { label: "风控检测配置", href: "./set.php?mod=risk", icon: ShieldCheck },
  { label: "结算规则配置", href: "./set.php?mod=settle", icon: PackageCheck },
  { label: "转账付款配置", href: "./set.php?mod=transfer", icon: WalletCards },
  { label: "快捷登录配置", href: "./set.php?mod=oauth", icon: Users },
  { label: "消息提醒配置", href: "./set.php?mod=notice", icon: Activity },
  {
    label: "实名认证配置",
    href: "./set.php?mod=certificate",
    icon: ShieldCheck,
  },
  { label: "网站公告配置", href: "./gonggao.php", icon: FileText },
  { label: "首页模板配置", href: "./set.php?mod=template", icon: Store },
  { label: "邮箱与短信配置", href: "./set.php?mod=mail", icon: FileText },
  { label: "网站 Logo 上传", href: "./set.php?mod=upimg", icon: ArrowUpRight },
  { label: "计划任务配置", href: "./set.php?mod=cron", icon: RefreshCw },
  { label: "中转代理配置", href: "./set.php?mod=proxy", icon: ShieldCheck },
  { label: "微信客服支付", href: "./set_wxkf.php", icon: Store },
  { label: "管理员账户", href: "./set.php?mod=account", icon: Users },
  {
    section: "其他功能",
    label: "风控记录",
    href: "./risk.php",
    icon: ShieldCheck,
  },
  { label: "登录日志", href: "./log.php", icon: FileText },
  { label: "数据清理", href: "./clean.php", icon: RefreshCw },
  { label: "获取用户标识", href: "./gettoken.php", icon: ShieldCheck },
  {
    label: "支付交易投诉",
    href: "./complain.php",
    icon: MessageCircle,
    feature: "complain",
  },
  {
    label: "渠道商户违规记录",
    href: "./mchrisk.php",
    icon: ShieldCheck,
    feature: "mchrisk",
  },
]

// 与 user/head.php 中的用户中心、查询、其他三组入口保持一致。
const merchantNav: NavItem[] = [
  { section: "概览", label: "用户中心", href: "./", icon: LayoutDashboard },
  {
    section: "个人资料",
    label: "API 信息",
    href: "./userinfo.php?mod=api",
    icon: ShieldCheck,
  },
  { label: "修改资料", href: "./editinfo.php", icon: Store },
  { label: "修改密码", href: "./userinfo.php?mod=account", icon: Settings },
  {
    label: "实名认证",
    href: "./certificate.php",
    icon: ShieldCheck,
    feature: "cert",
  },
  {
    label: "保证金",
    href: "./deposit.php",
    icon: WalletCards,
    feature: "deposit",
  },
  { section: "查询", label: "订单记录", href: "./order.php", icon: FileText },
  { label: "结算记录", href: "./settle.php", icon: PackageCheck },
  { label: "资金明细", href: "./record.php", icon: BarChart3 },
  {
    label: "申请提现",
    href: "./apply.php",
    icon: ArrowUpRight,
    feature: "withdraw",
  },
  {
    label: "余额充值",
    href: "./recharge.php",
    icon: CircleDollarSign,
    feature: "recharge",
  },
  {
    label: "购买会员",
    href: "./groupbuy.php",
    icon: Store,
    feature: "groupbuy",
  },
  {
    label: "授权域名",
    href: "./domain.php",
    icon: ShieldCheck,
    feature: "domain",
  },
  {
    label: "交易投诉",
    href: "./complain.php",
    icon: MessageCircle,
    feature: "complain",
  },
  {
    label: "商户违规记录",
    href: "./mchrisk.php",
    icon: ShieldCheck,
    feature: "mchrisk",
  },
  {
    section: "其他",
    label: "代付管理",
    href: "./transfer.php",
    icon: ArrowUpRight,
    feature: "transfer",
  },
  {
    label: "聚合收款",
    href: "./onecode.php",
    icon: CreditCard,
    feature: "onecode",
  },
  {
    label: "邀请返现",
    href: "./invite.php",
    icon: Users,
    feature: "invite",
  },
  { section: "帮助", label: "开发文档", href: "/doc.html", icon: BookOpen },
]

function featureEnabled(features: JsonObject, key: string, fallback = true) {
  const value = features[key]
  if (value === undefined) return fallback
  return value === true || value === 1 || value === "1"
}

function safeExternalHref(value: unknown) {
  if (typeof value !== "string" || value.trim() === "") return null
  try {
    const url = new URL(value, window.location.href)
    return url.protocol === "http:" || url.protocol === "https:"
      ? url.href
      : null
  } catch {
    return null
  }
}

function getVisibleNav(items: NavItem[], features: JsonObject) {
  return items.filter(
    (item) => !item.feature || featureEnabled(features, item.feature)
  )
}

function getMerchantNav(features: JsonObject) {
  const items = getVisibleNav(merchantNav, features)
  const externalItems: NavItem[] = []
  const qqGroup = safeExternalHref(features.qqqun)
  const appUrl = safeExternalHref(features.appurl)
  if (qqGroup) {
    externalItems.push({
      label: "产品QQ群",
      href: qqGroup,
      icon: Users,
      external: true,
    })
  }
  if (appUrl) {
    externalItems.push({
      label: "APP下载",
      href: appUrl,
      icon: ArrowUpRight,
      external: true,
    })
  }
  return [...items, ...externalItems]
}

function normalizeRoutePath(pathname: string) {
  const path = pathname.replace(/\/+$/, "") || "/"
  return path.endsWith("/index.php") ? path.slice(0, -10) || "/" : path
}

function isNavItemActive(href: string) {
  if (typeof window === "undefined") return false
  const target = new URL(href, window.location.href)
  const current = new URL(window.location.href)
  if (
    normalizeRoutePath(target.pathname) !== normalizeRoutePath(current.pathname)
  ) {
    return false
  }
  return !target.search || target.search === current.search
}

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

function Brand({
  compact = false,
  name = "Rainbow Pay",
}: {
  compact?: boolean
  name?: string
}) {
  return (
    <div className="flex min-w-0 items-center gap-3">
      <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
        <CircleDollarSign className="size-5" aria-hidden="true" />
      </div>
      {!compact && (
        <div className="min-w-0 leading-tight">
          <p className="truncate font-semibold tracking-tight">{name}</p>
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
      {items.map(({ label, href, icon: Icon, section, external }) => {
        const active = isNavItemActive(href)
        return (
          <React.Fragment key={href}>
            {section && (
              <p className="px-3 pt-4 pb-1 text-[11px] font-medium tracking-wider text-muted-foreground first:pt-0">
                {section}
              </p>
            )}
            <Button
              asChild
              variant={active ? "secondary" : "ghost"}
              className={cn(
                "h-10 justify-start gap-3 rounded-xl px-3 font-normal",
                active && "font-medium"
              )}
            >
              <a
                href={href}
                onClick={onNavigate}
                aria-current={active ? "page" : undefined}
                target={external ? "_blank" : undefined}
                rel={external ? "noopener noreferrer" : undefined}
              >
                <Icon
                  className="size-4 text-muted-foreground"
                  aria-hidden="true"
                />
                <span>{label}</span>
                {active && (
                  <ChevronRight
                    className="ml-auto size-4 text-muted-foreground"
                    aria-hidden="true"
                  />
                )}
              </a>
            </Button>
          </React.Fragment>
        )
      })}
    </nav>
  )
}

function WorkspaceShell({
  children,
  kind,
  title,
  description,
  sitename = "Rainbow Pay",
  features,
}: {
  children: React.ReactNode
  kind: "admin" | "merchant"
  title: string
  description: string
  sitename?: string
  features?: JsonObject
}) {
  const { resolvedTheme, setTheme } = useTheme()
  const dark = resolvedTheme === "dark"
  const [mobileOpen, setMobileOpen] = React.useState(false)
  const nav =
    kind === "admin"
      ? getVisibleNav(adminNav, features ?? {})
      : getMerchantNav(features ?? {})
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
                  <Brand name={sitename} />
                </SheetTitle>
                <SheetDescription>快速访问常用功能</SheetDescription>
              </SheetHeader>
              <ScrollArea className="h-[calc(100vh-118px)] px-4 py-5">
                <NavLinks items={nav} onNavigate={() => setMobileOpen(false)} />
              </ScrollArea>
            </SheetContent>
          </Sheet>
          <div className="hidden md:block">
            <Brand name={sitename} />
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
              asChild
              variant="ghost"
              size="icon"
              className="hidden rounded-xl sm:inline-flex"
            >
              <a href="/doc.html" aria-label="打开开发文档">
                <LifeBuoy className="size-4" />
              </a>
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
                <DropdownMenuGroup>
                  <DropdownMenuItem asChild>
                    <a
                      href={
                        kind === "admin"
                          ? "./set.php?mod=account"
                          : "editinfo.php"
                      }
                    >
                      <Settings data-icon="inline-start" />
                      账户设置
                    </a>
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <a href="/doc.html" target="_blank" rel="noreferrer">
                      <FileText data-icon="inline-start" />
                      开发文档
                    </a>
                  </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                  <DropdownMenuItem asChild>
                    <a
                      href={
                        kind === "admin"
                          ? "./login.php?logout"
                          : "login.php?logout"
                      }
                    >
                      <LogOut data-icon="inline-start" />
                      退出登录
                    </a>
                  </DropdownMenuItem>
                </DropdownMenuGroup>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </header>
      <div className="mx-auto flex max-w-[1440px]">
        <aside className="sticky top-16 hidden h-[calc(100vh-4rem)] w-64 shrink-0 flex-col border-r bg-background/60 p-4 md:flex">
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
              <span className="size-1.5 rounded-full bg-primary" />
              运行正常
            </Badge>
          </div>
          <ScrollArea className="min-h-0 flex-1 pr-2">
            <NavLinks items={nav} />
          </ScrollArea>
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
  brandName = "Rainbow Pay",
}: {
  eyebrow: string
  title: string
  description: string
  action?: React.ReactNode
  brandName?: string
}) {
  return (
    <div className="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div className="min-w-0">
        <Breadcrumb className="mb-3">
          <BreadcrumbList>
            <BreadcrumbItem>
              <BreadcrumbLink href="/">{brandName}</BreadcrumbLink>
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

function LegacyContentSlot({ className }: { className?: string } = {}) {
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

const documentationNav: Array<{
  label: string
  href: string
  icon: React.ElementType
}> = [
  { label: "接口说明", href: "/doc/index.html", icon: BookOpen },
  { label: "签名规则", href: "/doc/sign_note.html", icon: ShieldCheck },
  { label: "支付方式", href: "/doc/paytype.html", icon: CreditCard },
  { label: "页面跳转支付", href: "/doc/pay_submit.html", icon: ArrowUpRight },
  { label: "统一下单接口", href: "/doc/pay_create.html", icon: FileText },
  { label: "订单查询", href: "/doc/pay_query.html", icon: FileText },
  { label: "支付结果通知", href: "/doc/pay_notify.html", icon: Activity },
  { label: "订单退款", href: "/doc/pay_refund.html", icon: WalletCards },
  {
    label: "订单退款查询",
    href: "/doc/pay_refundquery.html",
    icon: WalletCards,
  },
  { label: "关闭订单", href: "/doc/pay_close.html", icon: ShieldCheck },
  { label: "商户信息", href: "/doc/merchant_info.html", icon: Users },
  { label: "商户订单", href: "/doc/merchant_orders.html", icon: FileText },
  { label: "转账发起", href: "/doc/transfer_submit.html", icon: ArrowUpRight },
  { label: "转账查询", href: "/doc/transfer_query.html", icon: FileText },
  {
    label: "余额查询",
    href: "/doc/transfer_balance.html",
    icon: CircleDollarSign,
  },
  { label: "SDK 下载", href: "/doc/sdk.html", icon: PackageCheck },
  { label: "服务条款", href: "/agreement.html", icon: ShieldCheck },
  { label: "旧版接口文档", href: "/doc_old.html", icon: FileText },
  { label: "微信支付教程", href: "/wx.html", icon: CreditCard },
]

function DocumentationNav({
  active,
  onNavigate,
}: {
  active: string
  onNavigate?: () => void
}) {
  return (
    <nav className="grid gap-1" aria-label="开发文档目录">
      {documentationNav.map(({ label, href, icon: Icon }) => {
        const activeItem =
          href.endsWith(`${active}.html`) ||
          (active === "index" && href.endsWith("/index.html"))
        return (
          <Button
            key={href}
            asChild
            variant={activeItem ? "secondary" : "ghost"}
            className={cn(
              "h-10 justify-start gap-3 rounded-xl px-3 font-normal",
              activeItem && "font-medium"
            )}
          >
            <a href={href} onClick={onNavigate}>
              <Icon
                className="size-4 text-muted-foreground"
                aria-hidden="true"
              />
              <span>{label}</span>
            </a>
          </Button>
        )
      })}
    </nav>
  )
}

function DocumentationShell({ config }: { config?: JsonObject }) {
  const { resolvedTheme, setTheme } = useTheme()
  const [mobileOpen, setMobileOpen] = React.useState(false)
  const active = String(config?.doc ?? "index")
  const title = String(config?.title ?? "开发文档")
  const sitename = String(config?.sitename ?? "Rainbow Pay")
  const dark = resolvedTheme === "dark"

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
                aria-label="打开文档目录"
              >
                <Menu className="size-5" />
              </Button>
            </SheetTrigger>
            <SheetContent side="left" className="w-[290px] p-0">
              <SheetHeader className="border-b px-5 py-4 text-left">
                <SheetTitle>
                  <Brand name={sitename} />
                </SheetTitle>
                <SheetDescription>快速浏览接口文档</SheetDescription>
              </SheetHeader>
              <ScrollArea className="h-[calc(100vh-118px)] px-4 py-5">
                <DocumentationNav
                  active={active}
                  onNavigate={() => setMobileOpen(false)}
                />
              </ScrollArea>
            </SheetContent>
          </Sheet>
          <div className="hidden md:block">
            <Brand name={sitename} />
          </div>
          <Separator
            orientation="vertical"
            className="mx-2 hidden h-6 md:block"
          />
          <div className="min-w-0">
            <p className="truncate text-sm font-medium">开发文档</p>
            <p className="hidden truncate text-xs text-muted-foreground sm:block">
              {sitename} API 与接入指南
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
              asChild
              variant="outline"
              className="hidden rounded-xl sm:inline-flex"
            >
              <a href="/">返回官网</a>
            </Button>
          </div>
        </div>
      </header>
      <div className="mx-auto flex max-w-[1440px]">
        <aside className="sticky top-16 hidden h-[calc(100vh-4rem)] w-64 shrink-0 border-r bg-background/60 p-4 md:block">
          <div className="mb-6 rounded-2xl border bg-card p-4 shadow-sm">
            <p className="text-xs font-medium tracking-wide text-muted-foreground">
              文档中心
            </p>
            <p className="mt-1 font-semibold">{title}</p>
            <Badge
              variant="secondary"
              className="mt-3 gap-1.5 rounded-lg font-normal"
            >
              <BookOpen className="size-3.5" />
              持续更新
            </Badge>
          </div>
          <ScrollArea className="h-[calc(100vh-220px)] pr-2">
            <DocumentationNav active={active} />
          </ScrollArea>
        </aside>
        <main className="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
          <PageHeading
            eyebrow="开发文档"
            title={title}
            description="使用统一的接口规范接入收款、退款、商户与代付能力。"
            brandName={sitename}
          />
          <LegacyContentSlot />
        </main>
      </div>
    </div>
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
    blue: "bg-primary/10 text-primary",
    green: "bg-secondary text-secondary-foreground",
    amber: "bg-accent text-accent-foreground",
    violet: "bg-muted text-muted-foreground",
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
              <ArrowUpRight className="size-3 text-primary" />
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
          <RefreshCw data-icon="inline-start" />
          重新加载
        </Button>
      </AlertDescription>
    </Alert>
  )
}

function AdminDashboard({ config }: { config?: JsonObject }) {
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
  const sitename = String(config?.sitename ?? "Rainbow Pay")
  const features = objectOf(config, "features")
  return (
    <WorkspaceShell
      kind="admin"
      title="平台运营"
      description="统一管理订单、商户、支付通道与结算"
      sitename={sitename}
      features={features}
    >
      <PageHeading
        eyebrow="平台首页"
        title="运营总览"
        description="实时掌握支付业务的核心指标与近期趋势。"
        brandName={sitename}
        action={
          <Button onClick={load} variant="outline" className="rounded-xl">
            <RefreshCw data-icon="inline-start" />
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
              <CardHeader className="flex flex-row items-start justify-between gap-3">
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
              <CardContent className="grid gap-2 sm:grid-cols-2">
                <Button
                  asChild
                  variant="outline"
                  className="h-11 justify-between rounded-xl"
                >
                  <a href="./order.php">
                    <span className="flex items-center gap-2">
                      <FileText className="size-4 text-primary" />
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
                      <Users className="size-4 text-secondary-foreground" />
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
                      <CreditCard className="size-4 text-primary" />
                      支付通道
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
                <Button
                  asChild
                  variant="outline"
                  className="h-11 justify-between rounded-xl"
                >
                  <a href="./transfer.php">
                    <span className="flex items-center gap-2">
                      <WalletCards className="size-4 text-accent-foreground" />
                      付款记录
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
                <Button
                  asChild
                  variant="outline"
                  className="h-11 justify-between rounded-xl"
                >
                  <a href="./slist.php">
                    <span className="flex items-center gap-2">
                      <PackageCheck className="size-4 text-muted-foreground" />
                      结算管理
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
                <Button
                  asChild
                  variant="outline"
                  className="h-11 justify-between rounded-xl"
                >
                  <a href="./set.php?mod=site">
                    <span className="flex items-center gap-2">
                      <Settings className="size-4 text-muted-foreground" />
                      网站设置
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
  const sitename = String(config?.sitename ?? "Rainbow Pay")
  const features = objectOf(config, "features")
  return (
    <WorkspaceShell
      kind="merchant"
      title="商户工作台"
      description="收款、结算与接口配置一站式管理"
      sitename={sitename}
      features={features}
    >
      <PageHeading
        eyebrow="用户中心"
        title="欢迎回来"
        description="这是你的商户经营概览，重要状态会在这里第一时间提醒。"
        brandName={sitename}
        action={
          <div className="flex gap-2">
            <Button asChild variant="outline" className="rounded-xl">
              <a href="userinfo.php?mod=api">
                <ShieldCheck data-icon="inline-start" />
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
                              <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted">
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
              <CardContent className="flex flex-col gap-3">
                <div className="flex items-center gap-3 rounded-xl border bg-primary/5 p-3">
                  <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
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
                <Button
                  asChild
                  variant="outline"
                  className="h-11 w-full justify-between rounded-xl"
                >
                  <a href="./record.php">
                    <span className="flex items-center gap-2">
                      <BarChart3 className="size-4" />
                      查看资金明细
                    </span>
                    <ChevronRight className="size-4 text-muted-foreground" />
                  </a>
                </Button>
                {featureEnabled(features, "withdraw") && (
                  <Button
                    asChild
                    variant="outline"
                    className="h-11 w-full justify-between rounded-xl"
                  >
                    <a href="./apply.php">
                      <span className="flex items-center gap-2">
                        <ArrowUpRight className="size-4" />
                        申请提现
                      </span>
                      <ChevronRight className="size-4 text-muted-foreground" />
                    </a>
                  </Button>
                )}
                <Button
                  asChild
                  variant="outline"
                  className="h-11 w-full justify-between rounded-xl"
                >
                  <a href="./userinfo.php?mod=account">
                    <span className="flex items-center gap-2">
                      <ShieldCheck className="size-4" />
                      修改密码
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
  const goBack = () => {
    try {
      if (
        document.referrer &&
        new URL(document.referrer).origin === window.location.origin
      ) {
        window.history.back()
        return
      }
    } catch {
      // 直接打开收银台时回到官网。
    }
    window.location.assign("/")
  }
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
          <div className="flex min-w-0 items-center gap-3">
            <Brand name={config?.sitename ?? "Rainbow Pay"} />
            <Separator orientation="vertical" className="h-6" />
            <Badge variant="secondary" className="rounded-lg font-normal">
              安全收银台
            </Badge>
          </div>
          <div className="hidden items-center gap-1.5 text-xs text-muted-foreground sm:flex">
            <ShieldCheck className="size-4 text-primary" />
            支付过程已加密
          </div>
        </header>
        {config?.other ? (
          <Card className="rounded-2xl shadow-sm">
            <CardContent className="flex flex-col gap-4 p-5 sm:p-7">
              <Alert className="rounded-2xl">
                <AlertTitle>当前支付方式暂时维护</AlertTitle>
                <AlertDescription>
                  请返回并选择其他可用支付方式。
                </AlertDescription>
              </Alert>
              <Button
                type="button"
                variant="outline"
                className="h-11 w-full rounded-xl"
                onClick={goBack}
              >
                返回上一页
              </Button>
            </CardContent>
          </Card>
        ) : (
          <Card className="rounded-2xl shadow-sm">
            <CardHeader className="border-b">
              <CardTitle className="text-lg">确认订单</CardTitle>
              <CardDescription>请核对订单信息后选择支付方式</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-5 p-5 sm:grid-cols-[1fr_auto] sm:p-7">
              <div className="flex flex-col gap-3 text-sm">
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
                  <Button
                    variant={
                      selected === String(type.id) ? "secondary" : "outline"
                    }
                    type="button"
                    key={String(type.id)}
                    aria-pressed={selected === String(type.id)}
                    onClick={() => setSelected(String(type.id))}
                    className={cn(
                      "group flex h-auto min-h-16 w-full items-center gap-3 rounded-xl p-4 text-left whitespace-normal transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-sm",
                      selected === String(type.id) &&
                        "border-primary ring-2 ring-primary/15"
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
                  </Button>
                ))}
              </CardContent>
            </Card>
            <div className="mt-5 flex flex-col items-stretch gap-3 rounded-2xl border bg-background p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
              <p className="flex items-center gap-2 text-xs text-muted-foreground">
                <ShieldCheck className="size-4 text-primary" />
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
                    <Loader2
                      className="animate-spin"
                      data-icon="inline-start"
                    />
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
  if (view === "gold-plan")
    return <GoldPlanView config={config as GoldPlanConfig | undefined} />
  if (view === "legacy-auth")
    return <LegacyAuthShell config={config as LegacyShellConfig | undefined} />
  if (view === "gateway-shell")
    return <GatewayShell config={config as LegacyShellConfig | undefined} />
  if (view === "installer-shell")
    return <InstallerShell config={config as LegacyShellConfig | undefined} />
  if (view === "public-legacy-shell")
    return (
      <PublicLegacyShell config={config as LegacyShellConfig | undefined} />
    )
  if (view === "transfer-confirm")
    return (
      <TransferConfirmView
        config={config as TransferConfirmConfig | undefined}
      />
    )
  if (view === "documentation-shell")
    return <DocumentationShell config={config as JsonObject | undefined} />
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
        sitename={String(shellConfig.sitename ?? "Rainbow Pay")}
        features={objectOf(shellConfig, "features")}
      >
        <LegacyContentSlot className="epay-legacy-workspace-surface" />
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
        sitename={String(shellConfig.sitename ?? "Rainbow Pay")}
        features={objectOf(shellConfig, "features")}
      >
        <LegacyContentSlot className="epay-legacy-workspace-surface" />
      </WorkspaceShell>
    )
  return <AdminDashboard config={config as JsonObject | undefined} />
}
