import { ArrowLeft, ShieldCheck } from "lucide-react"

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
import { Checkbox } from "@/components/ui/checkbox"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { cn } from "@/lib/utils"

type JsonObject = Record<string, unknown>
type AuthMode = "admin-login" | "user-login" | "user-register" | "user-recovery"

type AuthViewProps = { mode: AuthMode; config?: JsonObject }

function enabled(config: JsonObject, key: string) {
  const value = config[key]
  return (
    value === true ||
    value === 1 ||
    value === "1" ||
    value === -1 ||
    value === "-1"
  )
}

function isOpen(value: unknown) {
  return (
    value !== false &&
    value !== 0 &&
    value !== "0" &&
    value !== null &&
    value !== undefined
  )
}

function Brand({
  compact = false,
  inverse = false,
}: {
  compact?: boolean
  inverse?: boolean
}) {
  return (
    <div className="flex items-center gap-3">
      <div className="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
        <span className="text-sm font-semibold">RP</span>
      </div>
      {!compact && (
        <div className="leading-tight">
          <p className="font-semibold tracking-tight">Rainbow Pay</p>
          <p
            className={cn(
              "text-[11px]",
              inverse ? "text-primary-foreground/70" : "text-muted-foreground"
            )}
          >
            支付运营工作台
          </p>
        </div>
      )}
    </div>
  )
}

export function AuthView({ mode, config = {} }: AuthViewProps) {
  const admin = mode === "admin-login"
  const register = mode === "user-register"
  const recovery = mode === "user-recovery"
  const keyMode = mode === "user-login" && config.keyMode === true
  const verifyType = String(config.verifytype ?? "0")
  const siteName = String(config.sitename ?? "Rainbow Pay")
  const connect = (type: string) => {
    const legacyWindow = window as Window & {
      connect?: (value: string) => void
    }
    legacyWindow.connect?.(type)
  }
  return (
    <div className="min-h-svh bg-muted/30 text-foreground">
      <div className="grid min-h-svh lg:grid-cols-[minmax(0,1fr)_minmax(420px,560px)]">
        <section className="relative hidden overflow-hidden bg-primary p-10 text-primary-foreground lg:flex lg:flex-col lg:justify-between">
          <div className="absolute -top-32 -right-32 size-96 rounded-full border border-white/10" />
          <div className="absolute -bottom-48 -left-20 size-[28rem] rounded-full border border-white/10" />
          <Brand inverse />
          <div className="relative max-w-lg">
            <Badge
              variant="secondary"
              className="mb-5 rounded-lg border-white/10 bg-white/10 text-primary-foreground"
            >
              SAFE PAYMENT INFRASTRUCTURE
            </Badge>
            <h1 className="text-4xl font-semibold tracking-tight xl:text-5xl">
              让每一笔收款，都清晰可控。
            </h1>
            <p className="mt-5 max-w-md text-base leading-8 text-primary-foreground/75">
              统一管理订单、结算与支付通道，使用可靠组件构建更稳定的业务体验。
            </p>
            <div className="mt-8 flex flex-wrap gap-2 text-sm text-primary-foreground/80">
              <span className="rounded-full border border-white/15 px-3 py-1.5">
                多渠道收款
              </span>
              <span className="rounded-full border border-white/15 px-3 py-1.5">
                实时数据
              </span>
              <span className="rounded-full border border-white/15 px-3 py-1.5">
                安全风控
              </span>
            </div>
          </div>
          <p className="text-xs text-primary-foreground/60">
            © 2016—{new Date().getFullYear()} {siteName}
          </p>
        </section>
        <main className="flex items-center justify-center p-4 sm:p-8">
          <div className="w-full max-w-md">
            <div className="mb-7 flex items-center justify-between lg:hidden">
              <Brand compact />
              <Badge variant="secondary" className="rounded-lg">
                安全登录
              </Badge>
            </div>
            <Card className="rounded-2xl border bg-card shadow-lg shadow-primary/5">
              <CardHeader className="space-y-2 p-6 sm:p-8">
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <CardTitle className="text-2xl tracking-tight">
                      {admin
                        ? "管理员登录"
                        : register
                          ? "申请商户"
                          : recovery
                            ? "找回密码"
                            : "欢迎回来"}
                    </CardTitle>
                    <CardDescription className="mt-1">
                      {admin
                        ? "登录平台运营工作台"
                        : register
                          ? "完成资料即可开始接入支付"
                          : recovery
                            ? "验证身份后设置新的登录密码"
                            : "请输入你的商户信息"}
                    </CardDescription>
                  </div>
                  <div className="hidden size-10 items-center justify-center rounded-xl bg-primary/10 text-primary sm:flex">
                    <ShieldCheck className="size-5" />
                  </div>
                </div>
              </CardHeader>
              <CardContent className="p-6 pt-0 sm:p-8 sm:pt-0">
                {register ? (
                  <RegisterForm config={config} verifyType={verifyType} />
                ) : recovery ? (
                  <RecoveryForm config={config} />
                ) : (
                  <LoginForm
                    admin={admin}
                    config={config}
                    keyMode={keyMode}
                    connect={connect}
                  />
                )}
              </CardContent>
              <CardFooter className="justify-center border-t p-5 text-xs text-muted-foreground">
                <a href="/" className="hover:text-primary">
                  {siteName}
                </a>
                <span className="mx-2">·</span>© 2016—{new Date().getFullYear()}
              </CardFooter>
            </Card>
          </div>
        </main>
      </div>
    </div>
  )
}

function LoginForm({
  admin,
  config,
  keyMode,
  connect,
}: {
  admin: boolean
  config: JsonObject
  keyMode: boolean
  connect: (type: string) => void
}) {
  if (admin) {
    return (
      <>
        <form
          id="login-form"
          name="form"
          className="space-y-4"
          onSubmit={(event) => {
            event.preventDefault()
            ;(
              window as Window & { submitlogin?: () => boolean }
            ).submitlogin?.()
          }}
        >
          <div className="space-y-2">
            <Label htmlFor="user">用户名</Label>
            <Input
              id="user"
              name="user"
              placeholder="请输入管理员用户名"
              autoComplete="username"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="pass">密码</Label>
            <Input
              id="pass"
              name="pass"
              type="password"
              placeholder="请输入密码"
              autoComplete="current-password"
              required
            />
          </div>
          {enabled(config, "verifycode") && (
            <div className="space-y-2">
              <Label htmlFor="code">验证码</Label>
              <div className="flex gap-2">
                <Input
                  id="code"
                  name="code"
                  placeholder="输入验证码"
                  autoComplete="off"
                  required
                />
                <Button
                  type="button"
                  variant="outline"
                  className="h-11 w-28 overflow-hidden rounded-xl bg-muted p-0"
                  title="点击更换验证码"
                >
                  <img
                    id="verifycode"
                    src="./code.php"
                    alt="验证码"
                    className="h-11 w-28 object-cover"
                  />
                </Button>
              </div>
            </div>
          )}
          <Button id="submit" type="submit" className="h-11 w-full rounded-xl">
            立即登录
          </Button>
          <Button
            type="button"
            variant="link"
            className="h-auto w-full text-sm text-muted-foreground"
            onClick={() =>
              (window as Window & { findpwd?: () => void }).findpwd?.()
            }
          >
            忘记密码
          </Button>
        </form>
        <form
          id="totp-form"
          className="hidden space-y-3"
          onSubmit={(event) => {
            event.preventDefault()
            ;(window as Window & { doTotp?: () => boolean }).doTotp?.()
          }}
        >
          <div className="rounded-xl border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-primary">
            TOTP 二次验证
          </div>
          <div className="space-y-2">
            <Label htmlFor="totp_code">动态口令</Label>
            <Input
              id="totp_code"
              name="totp_code"
              type="number"
              inputMode="numeric"
              placeholder="输入 6 位动态口令"
              autoComplete="one-time-code"
              required
            />
          </div>
          <Button type="submit" className="h-11 w-full rounded-xl">
            验证并登录
          </Button>
          <Button
            type="button"
            variant="link"
            className="h-auto w-full text-sm text-muted-foreground"
            onClick={() =>
              (window as Window & { findpwd?: () => void }).findpwd?.()
            }
          >
            忘记密码
          </Button>
        </form>
      </>
    )
  }

  const submitUserLogin = () => {
    const form = document.forms.namedItem("form")
    const type = form?.elements.namedItem("type") as HTMLInputElement | null
    const user = form?.elements.namedItem("user") as HTMLInputElement | null
    const pass = form?.elements.namedItem("pass") as HTMLInputElement | null
    const legacyWindow = window as Window & {
      submitLogin?: (
        loginType: string,
        loginUser: string,
        loginPass: string
      ) => void
    }
    legacyWindow.submitLogin?.(
      type?.value ?? (keyMode ? "0" : "1"),
      user?.value.trim() ?? "",
      pass?.value ?? ""
    )
  }

  return (
    <form
      name="form"
      className="space-y-4"
      onSubmit={(event) => {
        event.preventDefault()
        submitUserLogin()
      }}
    >
      <input
        type="hidden"
        name="csrf_token"
        value={String(config.csrf_token ?? "")}
      />
      <input type="hidden" name="type" value={keyMode ? "0" : "1"} />
      {!enabled(config, "close_keylogin") && (
        <ToggleGroup
          type="single"
          value={keyMode ? "key" : "password"}
          variant="outline"
          spacing={1}
          className="grid w-full grid-cols-2 rounded-xl bg-muted p-1 text-sm"
          onValueChange={(value) => {
            if (value === "key") window.location.href = "./login.php?m=key"
            if (value === "password") window.location.href = "./login.php"
          }}
        >
          <ToggleGroupItem value="password" className="w-full rounded-lg">
            密码登录
          </ToggleGroupItem>
          <ToggleGroupItem value="key" className="w-full rounded-lg">
            密钥登录
          </ToggleGroupItem>
        </ToggleGroup>
      )}
      <div className="space-y-2">
        <Label htmlFor="user">{keyMode ? "商户 ID" : "邮箱 / 手机号"}</Label>
        <Input
          id="user"
          name="user"
          placeholder={keyMode ? "请输入商户 ID" : "请输入邮箱或手机号"}
          autoComplete="username"
          required
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="pass">{keyMode ? "商户密钥" : "密码"}</Label>
        <Input
          id="pass"
          name="pass"
          type="password"
          placeholder={keyMode ? "请输入商户密钥" : "请输入密码"}
          autoComplete="current-password"
          required
        />
      </div>
      {enabled(config, "captcha_open_login") && (
        <div id="captcha" className="rounded-xl border bg-muted/30 p-3">
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
      )}
      <Button id="submit" type="submit" className="h-11 w-full rounded-xl">
        立即登录
      </Button>
      <div className="flex items-center justify-between gap-3 text-sm">
        <a
          href="findpwd.php"
          className="text-muted-foreground hover:text-primary"
        >
          找回密码
        </a>
        {isOpen(config.reg_open) && (
          <a href="reg.php" className="text-primary hover:underline">
            注册商户
          </a>
        )}
      </div>
      {!keyMode && (
        <div className="flex justify-center gap-2 pt-2">
          {enabled(config, "login_alipay") && (
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="rounded-xl"
              title="支付宝快捷登录"
              onClick={() => connect("alipay")}
            >
              支
            </Button>
          )}
          {enabled(config, "login_qq") && (
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="rounded-xl"
              title="QQ 快捷登录"
              onClick={() => connect("qq")}
            >
              Q
            </Button>
          )}
          {enabled(config, "login_wx") && (
            <Button
              type="button"
              variant="outline"
              size="icon"
              className="rounded-xl"
              title="微信快捷登录"
              onClick={() => connect("wx")}
            >
              微
            </Button>
          )}
        </div>
      )}
    </form>
  )
}

function RegisterForm({
  config,
  verifyType,
}: {
  config: JsonObject
  verifyType: string
}) {
  return (
    <form name="form" className="space-y-4">
      <input
        type="hidden"
        name="csrf_token"
        value={String(config.csrf_token ?? "")}
      />
      <input type="hidden" name="verifytype" value={verifyType} />
      {enabled(config, "reg_pay") && (
        <div className="rounded-xl border border-primary/20 bg-primary/5 px-4 py-3 text-sm">
          商户申请价格：
          <strong className="text-primary">
            ¥ {String(config.reg_pay_price ?? "0")}
          </strong>
        </div>
      )}
      <div className="space-y-2">
        <Label htmlFor={verifyType === "1" ? "phone" : "email"}>
          {verifyType === "1" ? "手机号" : "邮箱"}
        </Label>
        <Input
          id={verifyType === "1" ? "phone" : "email"}
          name={verifyType === "1" ? "phone" : "email"}
          type={verifyType === "1" ? "text" : "email"}
          placeholder={
            verifyType === "1"
              ? "手机号码（同时作为登录账号）"
              : "邮箱（同时作为登录账号）"
          }
          required
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="code">验证码</Label>
        <div className="flex gap-2">
          <Input
            id="code"
            name="code"
            placeholder={verifyType === "1" ? "短信验证码" : "邮箱验证码"}
            required
          />
          <Button
            id="sendcode"
            type="button"
            variant="outline"
            className="shrink-0 rounded-xl"
          >
            获取验证码
          </Button>
        </div>
        <div id="wait" className="hidden" />
      </div>
      <div className="space-y-2">
        <Label htmlFor="pwd">登录密码</Label>
        <Input
          id="pwd"
          name="pwd"
          type="password"
          placeholder="请输入你的密码"
          required
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="pwd2">确认密码</Label>
        <Input
          id="pwd2"
          name="pwd2"
          type="password"
          placeholder="请再次输入密码"
          required
        />
      </div>
      {enabled(config, "invite_open") && (
        <div className="space-y-2">
          <Label htmlFor="invitecode">邀请码</Label>
          <Input
            id="invitecode"
            name="invitecode"
            placeholder="请输入邀请码"
            required
          />
        </div>
      )}
      <div className="flex items-center gap-2 pt-1">
        <Checkbox id="agree" defaultChecked required />
        <Label
          htmlFor="agree"
          className="text-sm font-normal text-muted-foreground"
        >
          我已阅读并同意
          <a
            href="../agreement.html"
            target="_blank"
            rel="noreferrer"
            className="ml-1 text-primary hover:underline"
          >
            服务条款
          </a>
        </Label>
      </div>
      <Button id="submit" type="button" className="h-11 w-full rounded-xl">
        立即注册
      </Button>
      <Button
        asChild
        type="button"
        variant="outline"
        className="h-11 w-full rounded-xl"
      >
        <a href="login.php">
          <ArrowLeft className="mr-2 size-4" />
          返回登录
        </a>
      </Button>
    </form>
  )
}

function RecoveryForm({ config }: { config: JsonObject }) {
  const syncType = (value: string) => {
    const select = document.querySelector<HTMLSelectElement>(
      "select[name='type']"
    )
    if (!select) return
    select.value = value
    select.dispatchEvent(new Event("change", { bubbles: true }))
  }
  return (
    <form name="form" className="space-y-4">
      <input
        type="hidden"
        name="csrf_token"
        value={String(config.csrf_token ?? "")}
      />
      <select
        name="type"
        className="hidden"
        defaultValue="email"
        aria-hidden="true"
        tabIndex={-1}
      >
        <option value="email">使用邮箱找回</option>
        <option value="phone">使用手机找回</option>
      </select>
      <div className="space-y-2">
        <Label>找回方式</Label>
        <Select defaultValue="email" onValueChange={syncType}>
          <SelectTrigger className="h-11 rounded-xl">
            <SelectValue placeholder="选择找回方式" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="email">使用邮箱找回</SelectItem>
            <SelectItem value="phone">使用手机找回</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div className="space-y-2">
        <Label htmlFor="account">邮箱 / 手机号</Label>
        <Input
          id="account"
          name="account"
          placeholder="输入邮箱或手机号"
          autoComplete="username"
          required
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="code">验证码</Label>
        <div className="flex gap-2">
          <Input id="code" name="code" placeholder="输入验证码" required />
          <Button
            id="sendcode"
            type="button"
            variant="outline"
            className="shrink-0 rounded-xl"
          >
            获取验证码
          </Button>
        </div>
        <div id="wait" className="hidden" />
      </div>
      <div className="space-y-2">
        <Label htmlFor="pwd">新密码</Label>
        <Input
          id="pwd"
          name="pwd"
          type="password"
          placeholder="请输入新密码"
          autoComplete="new-password"
          required
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="pwd2">确认密码</Label>
        <Input
          id="pwd2"
          name="pwd2"
          type="password"
          placeholder="请再次输入密码"
          autoComplete="new-password"
          required
        />
      </div>
      <Button id="submit" type="button" className="h-11 w-full rounded-xl">
        确认提交
      </Button>
      <Button
        asChild
        type="button"
        variant="outline"
        className="h-11 w-full rounded-xl"
      >
        <a href="login.php">返回登录</a>
      </Button>
    </form>
  )
}
