import * as React from "react"
import { Clipboard, ExternalLink, Loader2, RefreshCw, Save, ShieldCheck, Trash2 } from "lucide-react"

import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Field, FieldDescription, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Textarea } from "@/components/ui/textarea"

type JsonObject = Record<string, unknown>
type TokenChannel = { value: string; label: string }

async function readResponse(response: Response) {
  const data = (await response.json().catch(() => ({}))) as JsonObject
  if (!response.ok || (data.code !== undefined && ![0, 200].includes(Number(data.code)))) {
    throw new Error(String(data.msg ?? "请求失败"))
  }
  return data
}

export type AdminTokenConfig = { title?: string; description?: string; sitename?: string; siteurl?: string; app?: string; csrf_token?: string; channels?: Record<string, readonly TokenChannel[]>; defaults?: Record<string, string> }
type MaintenanceAction = { label: string; description: string; endpoint: string; method?: "GET" | "POST"; destructive?: boolean }
export type AdminMaintenanceConfig = { title?: string; description?: string; sitename?: string; csrf_token?: string; actions?: readonly MaintenanceAction[] }
export type AdminSettlementBatchConfig = { title?: string; description?: string; sitename?: string; csrf_token?: string; type?: number; rows?: readonly JsonObject[]; channels?: readonly TokenChannel[] }
export type AdminAccountConfig = {
  title?: string
  description?: string
  sitename?: string
  csrf_token?: string
  username?: string
  accountEndpoint?: string
  payEndpoint?: string
}
export type AdminGroupPurchaseConfig = {
  title?: string
  description?: string
  sitename?: string
  csrf_token?: string
  group_buy?: number | string | boolean
  rows?: readonly JsonObject[]
}
type AdminDynamicField = {
  key: string
  label: string
  type?: string
  value?: unknown
  note?: string
  options?: unknown
}
export type AdminChannelConfig = {
  title?: string
  description?: string
  sitename?: string
  csrf_token?: string
  channelId?: string | number
  endpoint?: string
  saveEndpoint?: string
}
export type AdminChannelTestConfig = {
  title?: string
  description?: string
  sitename?: string
  csrf_token?: string
  channelId?: string | number
  channelName?: string
}
export type AdminTotpConfig = { title?: string; description?: string; sitename?: string; csrf_token?: string; enabled?: boolean }
export type AdminBatchConfig = { title?: string; description?: string; sitename?: string; csrf_token?: string; rows?: readonly JsonObject[] }
export type AdminRollConfig = { title?: string; description?: string; sitename?: string; csrf_token?: string; rollId?: string | number }

function tokenUrl(app: string, siteurl: string, channel: string, authType: string) {
  if (app === "wechat") return `${siteurl}user/openid.php?wechatid=${encodeURIComponent(channel)}`
  if (app === "alipayuid") return `${siteurl}user/openid.php?channel=${encodeURIComponent(channel)}`
  if (app === "apptoken") return `${siteurl}user/openid.php?act=${authType === "1" ? "app_auth_assign" : "app_auth"}&channel=${encodeURIComponent(channel)}`
  return ""
}

export function AdminTokenView({ config = {} }: { config?: AdminTokenConfig }) {
  const apps = [
    ["wechat", "微信公众号 OpenID"], ["applet", "微信小程序 OpenID"], ["alipayuid", "支付宝用户 ID"], ["apptoken", "支付宝应用授权 Token"],
  ] as const
  const [app, setApp] = React.useState(String(config.app ?? "wechat"))
  const channelOptions = config.channels?.[app] ?? []
  const [channel, setChannel] = React.useState(String(config.defaults?.[app] ?? channelOptions[0]?.value ?? ""))
  const [authType, setAuthType] = React.useState("0")
  const [url, setUrl] = React.useState("")
  const [loading, setLoading] = React.useState(false)
  const [notice, setNotice] = React.useState("")
  const changeApp = (next: string) => { setApp(next); setChannel(String(config.defaults?.[next] ?? (config.channels?.[next] ?? [])[0]?.value ?? "")); setUrl("") }
  const generate = async () => {
    if (!channel) { setNotice("当前没有可用通道"); return }
    if (app !== "applet") { setUrl(tokenUrl(app, String(config.siteurl ?? ""), channel, authType)); return }
    setLoading(true); setNotice("")
    try {
      const body = new URLSearchParams({ csrf_token: String(config.csrf_token ?? ""), channel })
      const response = await fetch("ajax.php?act=generate_wxa_link", { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      const data = (await response.json().catch(() => ({}))) as JsonObject
      if (!response.ok || Number(data.code) !== 0) throw new Error(String(data.msg ?? "生成链接失败"))
      setUrl(String(data.url ?? ""))
    } catch (error) { setNotice(error instanceof Error ? error.message : "生成链接失败") } finally { setLoading(false) }
  }
  const copy = async () => { if (!url) return; try { await navigator.clipboard.writeText(url); setNotice("链接已复制") } catch { setNotice("复制失败，请长按链接手动复制") } }
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-3xl flex-col gap-6"><header><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "获取用户标识"}</h1><p className="mt-1 text-sm text-muted-foreground">{config.description ?? "生成 OAuth 或 OpenID 获取链接。"}</p></header><Card className="rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle>生成授权链接</CardTitle><CardDescription>链接只应在对应的微信或支付宝客户端中打开。</CardDescription></CardHeader><CardContent className="pt-6"><FieldGroup className="grid gap-5 sm:grid-cols-2"><Field><FieldLabel>授权类型</FieldLabel><Select value={app} onValueChange={changeApp}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectGroup>{apps.map(([value, label]) => <SelectItem key={value} value={value}>{label}</SelectItem>)}</SelectGroup></SelectContent></Select></Field><Field><FieldLabel>支付通道</FieldLabel><Select value={channel || "__empty"} onValueChange={(value) => setChannel(value === "__empty" ? "" : value)}><SelectTrigger><SelectValue placeholder="选择通道" /></SelectTrigger><SelectContent><SelectGroup>{channelOptions.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectGroup></SelectContent></Select><FieldDescription>{channelOptions.length ? "通道配置来自后台支付接口。" : "请先在公众号/支付通道管理中配置可用通道。"}</FieldDescription></Field>{app === "apptoken" ? <Field><FieldLabel>授权方式</FieldLabel><Select value={authType} onValueChange={setAuthType}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectGroup><SelectItem value="0">基础应用授权</SelectItem><SelectItem value="1">指定应用授权</SelectItem></SelectGroup></SelectContent></Select></Field> : null}</FieldGroup><div className="mt-6 flex flex-wrap gap-2"><Button type="button" className="rounded-xl" onClick={() => void generate()} disabled={loading}>{loading ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <ShieldCheck data-icon="inline-start" />}生成获取链接</Button>{url ? <Button type="button" variant="outline" className="rounded-xl" onClick={() => void copy()}><Clipboard data-icon="inline-start" />复制链接</Button> : null}</div>{notice ? <Alert className="mt-5"><AlertTitle>提示</AlertTitle><AlertDescription>{notice}</AlertDescription></Alert> : null}{url ? <Field className="mt-6"><FieldLabel htmlFor="token-url">获取链接</FieldLabel><Input id="token-url" value={url} readOnly /><FieldDescription>请复制后在对应客户端打开，或使用下方链接直接打开。</FieldDescription><Button asChild variant="outline" className="w-fit rounded-xl"><a href={url} target="_blank" rel="noreferrer"><ExternalLink data-icon="inline-start" />打开链接</a></Button></Field> : null}</CardContent></Card></div></div>
}

type AccountNotice = { kind: "success" | "error"; text: string }

export function AdminAccountView({ config = {} }: { config?: AdminAccountConfig }) {
  const [username, setUsername] = React.useState(String(config.username ?? ""))
  const [accountValues, setAccountValues] = React.useState({ oldpwd: "", newpwd: "", newpwd2: "" })
  const [payValues, setPayValues] = React.useState({ oldpwd: "", newpwd: "", newpwd2: "" })
  const [pending, setPending] = React.useState<"account" | "pay" | "">("")
  const [notice, setNotice] = React.useState<AccountNotice | null>(null)

  const submit = async (kind: "account" | "pay", event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setPending(kind)
    setNotice(null)
    try {
      const values = kind === "account" ? accountValues : payValues
      const body = new URLSearchParams({
        csrf_token: String(config.csrf_token ?? ""),
        ...(kind === "account" ? { user: username } : {}),
        oldpwd: values.oldpwd,
        newpwd: values.newpwd,
        newpwd2: values.newpwd2,
      })
      const endpoint = kind === "account" ? config.accountEndpoint : config.payEndpoint
      const response = await fetch(String(endpoint ?? ""), {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
        body,
      })
      const data = await readResponse(response)
      setNotice({ kind: "success", text: String(data.msg ?? "保存成功") })
      if (kind === "account") setAccountValues({ oldpwd: "", newpwd: "", newpwd2: "" })
      else setPayValues({ oldpwd: "", newpwd: "", newpwd2: "" })
    } catch (error) {
      setNotice({ kind: "error", text: error instanceof Error ? error.message : "保存失败" })
    } finally {
      setPending("")
    }
  }

  const updateAccount = (key: keyof typeof accountValues, value: string) => setAccountValues((current) => ({ ...current, [key]: value }))
  const updatePay = (key: keyof typeof payValues, value: string) => setPayValues((current) => ({ ...current, [key]: value }))
  const passwordFields = [
    ["oldpwd", "当前密码"],
    ["newpwd", "新密码"],
    ["newpwd2", "确认新密码"],
  ] as const

  return (
    <div className="min-w-0 w-full">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <header>
          <p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p>
          <h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "管理员账户设置"}</h1>
          <p className="mt-1 max-w-3xl text-sm text-muted-foreground">{config.description ?? "分别更新后台登录凭据和支付密码。"}</p>
        </header>
        {notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "保存失败" : "保存成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}
        <div className="grid gap-6 lg:grid-cols-2">
          <Card className="rounded-2xl shadow-sm">
            <CardHeader className="border-b"><CardTitle>登录账号与密码</CardTitle><CardDescription>修改后台登录用户名或管理员密码。</CardDescription></CardHeader>
            <form onSubmit={(event) => void submit("account", event)}>
              <CardContent className="pt-6"><FieldGroup>
                <Field><FieldLabel htmlFor="admin-account-user">管理员账号</FieldLabel><Input id="admin-account-user" value={username} onChange={(event) => setUsername(event.target.value)} required /></Field>
                {passwordFields.map(([key, label]) => <Field key={key}><FieldLabel htmlFor={`admin-account-${key}`}>{label}</FieldLabel><Input id={`admin-account-${key}`} type="password" value={accountValues[key]} onChange={(event) => updateAccount(key, event.target.value)} required /></Field>)}
              </FieldGroup></CardContent>
              <CardFooter className="border-t"><Button type="submit" className="rounded-xl" disabled={pending !== ""}>{pending === "account" ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <ShieldCheck data-icon="inline-start" />}保存登录凭据</Button></CardFooter>
            </form>
          </Card>
          <Card className="rounded-2xl shadow-sm">
            <CardHeader className="border-b"><CardTitle>支付密码</CardTitle><CardDescription>支付密码用于结算、转账等高风险操作。</CardDescription></CardHeader>
            <form onSubmit={(event) => void submit("pay", event)}>
              <CardContent className="pt-6"><FieldGroup>{passwordFields.map(([key, label]) => <Field key={key}><FieldLabel htmlFor={`admin-pay-${key}`}>{label}</FieldLabel><Input id={`admin-pay-${key}`} type="password" value={payValues[key]} onChange={(event) => updatePay(key, event.target.value)} required /></Field>)}</FieldGroup></CardContent>
              <CardFooter className="border-t"><Button type="submit" className="rounded-xl" disabled={pending !== ""}>{pending === "pay" ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <ShieldCheck data-icon="inline-start" />}保存支付密码</Button></CardFooter>
            </form>
          </Card>
        </div>
      </div>
    </div>
  )
}

export function AdminGroupPurchaseView({ config = {} }: { config?: AdminGroupPurchaseConfig }) {
  const [groupBuy, setGroupBuy] = React.useState(Number(config.group_buy) === 1 || config.group_buy === true)
  const [rows, setRows] = React.useState<JsonObject[]>([...(config.rows ?? [])])
  const [pending, setPending] = React.useState("")
  const [notice, setNotice] = React.useState<AccountNotice | null>(null)
  const updateRow = (gid: string, key: string, value: string) => setRows((current) => current.map((row) => String(row.gid ?? row.id) === gid ? { ...row, [key]: value } : row))
  const post = async (endpoint: string, values: Record<string, string>) => {
    const body = new URLSearchParams({ csrf_token: String(config.csrf_token ?? ""), ...values })
    const response = await fetch(endpoint, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
    return readResponse(response)
  }
  const saveAll = async () => {
    setPending("save"); setNotice(null)
    try {
      const values: Record<string, string> = {}
      rows.forEach((row) => {
        const gid = String(row.gid ?? row.id ?? "")
        if (!gid) return
        values[`price[${gid}]`] = String(row.price ?? "")
        values[`expire[${gid}]`] = String(row.expire ?? "")
        values[`sort[${gid}]`] = String(row.sort ?? "")
        values[`visible[${gid}]`] = String(row.visible ?? "")
      })
      const data = await post("ajax_user.php?act=saveGroupPrice", values)
      setNotice({ kind: "success", text: String(data.msg ?? "用户组购买设置已保存") })
    } catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "保存失败" }) } finally { setPending("") }
  }
  const toggleGroupBuy = async (checked: boolean) => {
    setPending("toggle"); setNotice(null)
    try { const data = await post("ajax.php?act=set", { group_buy: checked ? "1" : "0" }); setGroupBuy(checked); setNotice({ kind: "success", text: String(data.msg ?? "购买开关已更新") }) }
    catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "保存失败" }) } finally { setPending("") }
  }
  const changeBuy = async (gid: string, status: boolean) => {
    setPending(`row-${gid}`); setNotice(null)
    try { const data = await post("ajax_user.php?act=saveGroup", { action: "changebuy", gid, status: status ? "1" : "0" }); setRows((current) => current.map((row) => String(row.gid ?? row.id) === gid ? { ...row, isbuy: status ? "1" : "0" } : row)); setNotice({ kind: "success", text: String(data.msg ?? "上架状态已更新") }) }
    catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "操作失败" }) } finally { setPending("") }
  }
  const available = rows.filter((row) => Number(row.isbuy) === 1)
  const unavailable = rows.filter((row) => Number(row.isbuy) !== 1)
  const input = (row: JsonObject, key: string, placeholder: string) => <Input className="min-w-28" value={String(row[key] ?? "")} onChange={(event) => updateRow(String(row.gid ?? row.id), key, event.target.value)} placeholder={placeholder} />
  const table = (items: JsonObject[], editable: boolean) => <div className="overflow-x-auto"><Table className="min-w-[860px]"><TableHeader><TableRow><TableHead>GID</TableHead><TableHead>用户组名称</TableHead>{editable ? <><TableHead>售价</TableHead><TableHead>有效期（月）</TableHead><TableHead>排序</TableHead><TableHead>可见范围</TableHead></> : null}<TableHead className="text-right">操作</TableHead></TableRow></TableHeader><TableBody>{items.length ? items.map((row) => { const gid = String(row.gid ?? row.id ?? ""); const listed = Number(row.isbuy) === 1; return <TableRow key={gid}><TableCell className="font-mono text-xs">{gid}</TableCell><TableCell>{String(row.name ?? "—")}</TableCell>{editable ? <><TableCell>{input(row, "price", "售价")}</TableCell><TableCell>{input(row, "expire", "0 为永久")}</TableCell><TableCell>{input(row, "sort", "数字越小越靠前")}</TableCell><TableCell>{input(row, "visible", "留空为全部")}</TableCell></> : null}<TableCell className="text-right"><Button size="sm" variant={listed ? "outline" : "default"} className="rounded-lg" disabled={pending !== ""} onClick={() => void changeBuy(gid, !listed)}>{listed ? "下架" : "上架"}</Button></TableCell></TableRow> }) : <TableRow><TableCell colSpan={editable ? 7 : 3} className="h-24 text-center text-muted-foreground">暂无记录</TableCell></TableRow>}</TableBody></Table></div>
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-[1440px] flex-col gap-6"><header><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "用户组购买设置"}</h1><p className="mt-1 max-w-3xl text-sm text-muted-foreground">{config.description ?? "控制用户组购买开关、售价、有效期和可见范围。"}</p></header>{notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "操作失败" : "操作成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}<Card className="rounded-2xl shadow-sm"><CardContent className="flex flex-wrap items-center justify-between gap-4 pt-6"><div><p className="font-medium">用户组购买开关</p><p className="text-sm text-muted-foreground">关闭后，商户中心不会展示购买入口。</p></div><div className="flex items-center gap-3"><Checkbox checked={groupBuy} disabled={pending !== ""} onCheckedChange={(checked) => void toggleGroupBuy(checked === true)} /><span className="text-sm">{groupBuy ? "已开启" : "已关闭"}</span></div></CardContent></Card><Card className="overflow-hidden rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle className="text-base">可购买的用户组（{available.length}）</CardTitle><CardDescription>修改售价、有效期、排序和可见范围后统一保存。</CardDescription></CardHeader><CardContent className="p-0">{table(available, true)}</CardContent><CardFooter className="border-t"><Button className="rounded-xl" disabled={pending !== ""} onClick={() => void saveAll()}>{pending === "save" ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <Save data-icon="inline-start" />}保存全部</Button></CardFooter></Card><Card className="overflow-hidden rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle className="text-base">不可购买的用户组（{unavailable.length}）</CardTitle><CardDescription>可将用户组重新上架。</CardDescription></CardHeader><CardContent className="p-0">{table(unavailable, false)}</CardContent></Card></div></div>
}

function dynamicOptions(value: unknown): { value: string; label: string }[] {
  if (!value || typeof value !== "object") return []
  return Object.entries(value as Record<string, unknown>).map(([key, label]) => ({ value: key, label: String(label) }))
}

export function AdminChannelConfigView({ config = {} }: { config?: AdminChannelConfig }) {
  const [fields, setFields] = React.useState<AdminDynamicField[]>([])
  const [values, setValues] = React.useState<Record<string, string | string[]>>({})
  const [note, setNote] = React.useState("")
  const [title, setTitle] = React.useState(String(config.title ?? "支付通道密钥配置"))
  const [loading, setLoading] = React.useState(true)
  const [pending, setPending] = React.useState(false)
  const [notice, setNotice] = React.useState<AccountNotice | null>(null)
  const endpoint = String(config.endpoint ?? `ajax_pay.php?act=channelInfo&id=${encodeURIComponent(String(config.channelId ?? ""))}`)
  const load = React.useCallback(async () => {
    setLoading(true); setNotice(null)
    try {
      const response = await fetch(endpoint, { credentials: "same-origin" })
      const data = await readResponse(response)
      const payload = (data.data && typeof data.data === "object" ? data.data : {}) as JsonObject
      const nextFields = Array.isArray(payload.fields) ? payload.fields as AdminDynamicField[] : []
      setFields(nextFields)
      setTitle(String(payload.typename ? `${payload.typename} · ${config.title ?? "支付通道密钥配置"}` : config.title ?? "支付通道密钥配置"))
      setNote(String(payload.note ?? ""))
      setValues(Object.fromEntries(nextFields.map((field) => [field.key, Array.isArray(field.value) ? field.value.map(String) : String(field.value ?? "")])) )
    } catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "加载通道配置失败" }) } finally { setLoading(false) }
  }, [config.title, endpoint])
  React.useEffect(() => { const timer = window.setTimeout(() => void load(), 0); return () => window.clearTimeout(timer) }, [load])
  const update = (key: string, value: string | string[]) => setValues((current) => ({ ...current, [key]: value }))
  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setPending(true); setNotice(null)
    try {
      const body = new URLSearchParams({ csrf_token: String(config.csrf_token ?? "") })
      fields.forEach((field) => {
        const value = values[field.key]
        if (field.key === "apptype") body.set("isapptype", "1")
        if (Array.isArray(value)) value.forEach((item) => body.append(field.key === "apptype" ? "apptype[]" : `config[${field.key}][]`, item))
        else if (field.key === "appwxmp" || field.key === "appwxa") body.set(field.key, String(value ?? ""))
        else body.set(`config[${field.key}]`, String(value ?? ""))
      })
      const response = await fetch(String(config.saveEndpoint ?? `ajax_pay.php?act=saveChannelInfo&id=${encodeURIComponent(String(config.channelId ?? ""))}`), { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      const data = await readResponse(response)
      setNotice({ kind: "success", text: String(data.msg ?? "通道配置已保存") })
    } catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "保存失败" }) } finally { setPending(false) }
  }
  const control = (field: AdminDynamicField) => {
    const value = values[field.key] ?? (field.type === "checkbox" ? [] : "")
    if (field.type === "checkbox") return <div className="flex flex-wrap gap-3 rounded-xl border p-3">{dynamicOptions(field.options).map((option) => { const selected = Array.isArray(value) && value.includes(option.value); return <label key={option.value} className="flex items-center gap-2 text-sm"><Checkbox checked={selected} onCheckedChange={(checked) => { const current = Array.isArray(value) ? value : []; update(field.key, checked === true ? [...current, option.value] : current.filter((item) => item !== option.value)) }} />{option.label}</label> })}</div>
    if (field.type === "select") return <Select value={String(value || "__empty")} onValueChange={(next) => update(field.key, next === "__empty" ? "" : next)}><SelectTrigger className="w-full"><SelectValue placeholder={field.label} /></SelectTrigger><SelectContent><SelectGroup>{dynamicOptions(field.options).map((option) => <SelectItem key={option.value} value={option.value || "__empty"}>{option.label}</SelectItem>)}</SelectGroup></SelectContent></Select>
    if (field.type === "textarea") return <Textarea value={String(value)} onChange={(event) => update(field.key, event.target.value)} placeholder={field.note} />
    return <Input value={String(value)} onChange={(event) => update(field.key, event.target.value)} placeholder={field.note} type={/secret|key|password/i.test(field.key) ? "password" : "text"} />
  }
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-5xl flex-col gap-6"><header><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{title}</h1><p className="mt-1 text-sm text-muted-foreground">{config.description ?? "使用支付插件提供的字段配置通道密钥。"}</p></header>{notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "操作失败" : "操作成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}{note ? <Alert><AlertTitle>配置说明</AlertTitle><AlertDescription>{note}</AlertDescription></Alert> : null}<Card className="rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle>{title}</CardTitle><CardDescription>{loading ? "正在加载插件字段…" : `共 ${fields.length} 项配置`}</CardDescription></CardHeader><form onSubmit={submit}><CardContent className="pt-6"><FieldGroup className="grid gap-5 md:grid-cols-2">{fields.map((field) => <Field key={field.key} className={field.type === "textarea" || field.type === "checkbox" ? "md:col-span-2" : undefined}><FieldLabel>{field.label}</FieldLabel>{control(field)}{field.note ? <FieldDescription>{field.note}</FieldDescription> : null}</Field>)}</FieldGroup></CardContent><CardFooter className="flex flex-wrap gap-2 border-t"><Button type="submit" className="rounded-xl" disabled={loading || pending || fields.length === 0}>{pending ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <Save data-icon="inline-start" />}保存配置</Button><Button type="button" variant="outline" className="rounded-xl" disabled={pending} onClick={() => void load()}><RefreshCw data-icon="inline-start" />重新加载</Button><Button asChild type="button" variant="outline" className="rounded-xl"><a href="./pay_channel.php">返回通道列表</a></Button></CardFooter></form></Card></div></div>
}

export function AdminChannelTestView({ config = {} }: { config?: AdminChannelTestConfig }) {
  const [name, setName] = React.useState("支付测试")
  const [money, setMoney] = React.useState("1")
  const [url, setUrl] = React.useState("")
  const [pending, setPending] = React.useState(false)
  const [notice, setNotice] = React.useState<AccountNotice | null>(null)
  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setPending(true)
    setNotice(null)
    setUrl("")
    try {
      const body = new URLSearchParams({
        csrf_token: String(config.csrf_token ?? ""),
        channel: String(config.channelId ?? ""),
        subchannel: "0",
        name: name.trim(),
        money: money.trim(),
      })
      const response = await fetch("ajax_pay.php?act=testpay", {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
        body,
      })
      const data = await readResponse(response)
      setUrl(String(data.url ?? ""))
      setNotice({ kind: "success", text: "测试订单已创建，请打开收银台完成支付验证。" })
    } catch (error) {
      setNotice({ kind: "error", text: error instanceof Error ? error.message : "创建测试订单失败" })
    } finally {
      setPending(false)
    }
  }
  return (
    <div className="min-w-0 w-full">
      <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
        <header>
          <p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p>
          <h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "测试支付"}</h1>
          <p className="mt-1 text-sm text-muted-foreground">{config.description ?? `使用「${config.channelName ?? "当前通道"}」创建一笔测试订单。`}</p>
        </header>
        {notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "创建失败" : "创建成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}
        <Card className="rounded-2xl shadow-sm">
          <CardHeader className="border-b"><CardTitle>测试订单参数</CardTitle><CardDescription>请使用小额金额验证通道配置、收银台跳转和支付回调。</CardDescription></CardHeader>
          <form onSubmit={submit}>
            <CardContent className="pt-6"><FieldGroup className="grid gap-5 sm:grid-cols-2">
              <Field><FieldLabel htmlFor="channel-test-name">订单名称</FieldLabel><Input id="channel-test-name" value={name} onChange={(event) => setName(event.target.value)} required /></Field>
              <Field><FieldLabel htmlFor="channel-test-money">订单金额</FieldLabel><Input id="channel-test-money" type="number" min="0.01" step="0.01" inputMode="decimal" value={money} onChange={(event) => setMoney(event.target.value)} required /><FieldDescription>金额会受到系统全局最小/最大支付金额限制。</FieldDescription></Field>
            </FieldGroup>{url ? <Alert className="mt-5"><AlertTitle>测试收银台</AlertTitle><AlertDescription className="flex flex-wrap items-center gap-3"><span className="break-all font-mono text-xs">{url}</span><Button asChild size="sm" variant="outline" className="rounded-lg"><a href={url} target="_blank" rel="noreferrer"><ExternalLink data-icon="inline-start" />打开收银台</a></Button></AlertDescription></Alert> : null}</CardContent>
            <CardFooter className="flex flex-wrap gap-2 border-t"><Button type="submit" className="rounded-xl" disabled={pending}>{pending ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <ShieldCheck data-icon="inline-start" />}创建测试订单</Button><Button asChild type="button" variant="outline" className="rounded-xl"><a href="./pay_channel.php">返回通道列表</a></Button></CardFooter>
          </form>
        </Card>
      </div>
    </div>
  )
}

export function AdminTotpView({ config = {} }: { config?: AdminTotpConfig }) {
  const [action, setAction] = React.useState("generate")
  const [secret, setSecret] = React.useState("")
  const [code, setCode] = React.useState("")
  const [password, setPassword] = React.useState("")
  const [uri, setUri] = React.useState("")
  const [pending, setPending] = React.useState(false)
  const [notice, setNotice] = React.useState<AccountNotice | null>(null)
  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setPending(true); setNotice(null)
    try {
      const body = new URLSearchParams({ csrf_token: String(config.csrf_token ?? ""), action, secret, code, password })
      const response = await fetch("./set_totp.php", { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      const data = await readResponse(response)
      const generated = data.data && typeof data.data === "object" ? data.data as JsonObject : {}
      if (action === "generate") { setSecret(String(generated.secret ?? "")); setUri(String(generated.qrcode ?? "")) }
      else if (action === "close") { setSecret(""); setUri("") }
      setNotice({ kind: "success", text: String(data.msg ?? (action === "generate" ? "绑定密钥已生成" : "操作成功")) })
    } catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "操作失败" }) } finally { setPending(false) }
  }
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-3xl flex-col gap-6"><header><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "TOTP 二次验证"}</h1><p className="mt-1 text-sm text-muted-foreground">{config.description ?? "使用认证器保护管理员登录。"}</p></header>{notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "操作失败" : "操作成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}<Card className="rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle>{config.enabled ? "TOTP 已开启" : "TOTP 未开启"}</CardTitle><CardDescription>生成密钥后，将密钥添加到认证器，再输入当前验证码完成绑定。</CardDescription></CardHeader><form onSubmit={submit}><CardContent className="pt-6"><FieldGroup><Field><FieldLabel htmlFor="totp-action">操作</FieldLabel><Select value={action} onValueChange={setAction}><SelectTrigger id="totp-action"><SelectValue /></SelectTrigger><SelectContent><SelectGroup><SelectItem value="generate">生成新的绑定密钥</SelectItem><SelectItem value="bind">绑定 TOTP</SelectItem><SelectItem value="close">关闭 TOTP</SelectItem></SelectGroup></SelectContent></Select></Field><Field><FieldLabel htmlFor="totp-secret">绑定密钥</FieldLabel><Input id="totp-secret" value={secret} onChange={(event) => setSecret(event.target.value)} placeholder="生成后复制到认证器" /><FieldDescription>绑定操作需要填写生成的密钥；关闭操作不需要。</FieldDescription></Field><Field><FieldLabel htmlFor="totp-code">动态验证码</FieldLabel><Input id="totp-code" inputMode="numeric" value={code} onChange={(event) => setCode(event.target.value)} placeholder="6 位验证码" /></Field>{action === "close" ? <Field><FieldLabel htmlFor="totp-password">管理员密码</FieldLabel><Input id="totp-password" type="password" value={password} onChange={(event) => setPassword(event.target.value)} required /></Field> : null}</FieldGroup>{uri ? <Alert className="mt-5"><AlertTitle>绑定 URI</AlertTitle><AlertDescription className="break-all font-mono text-xs">{uri}</AlertDescription></Alert> : null}</CardContent><CardFooter className="border-t"><Button type="submit" className="rounded-xl" disabled={pending}>{pending ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <ShieldCheck data-icon="inline-start" />}执行操作</Button></CardFooter></form></Card></div></div>
}

export function AdminBatchView({ config = {} }: { config?: AdminBatchConfig }) {
  const [rows, setRows] = React.useState<JsonObject[]>([...(config.rows ?? [])])
  const [pending, setPending] = React.useState("")
  const [notice, setNotice] = React.useState<AccountNotice | null>(null)
  const [confirm, setConfirm] = React.useState<JsonObject | null>(null)
  const post = async (endpoint: string, values: Record<string, string> = {}) => {
    const body = new URLSearchParams({ csrf_token: String(config.csrf_token ?? ""), ...values })
    const response = await fetch(endpoint, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
    return readResponse(response)
  }
  const generate = async () => {
    setPending("generate"); setNotice(null)
    try { const data = await post("ajax_settle.php?act=create_batch"); setNotice({ kind: "success", text: String(data.msg ?? `批次 ${data.batch ?? ""} 已生成`) }); window.setTimeout(() => window.location.reload(), 600) }
    catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "生成批次失败" }) } finally { setPending("") }
  }
  const complete = async (row: JsonObject) => {
    const batch = String(row.batch ?? "")
    setConfirm(null); setPending(batch); setNotice(null)
    try { const data = await post("ajax_settle.php?act=complete_batch", { batch }); setRows((current) => current.map((item) => String(item.batch) === batch ? { ...item, status: "1" } : item)); setNotice({ kind: "success", text: String(data.msg ?? "批次已完成") }) }
    catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "更新批次失败" }) } finally { setPending("") }
  }
  const transferLinks = (batch: string) => [[1, "支付宝"], [2, "微信"], [3, "QQ 钱包"], [4, "银行卡"]].map(([type, label]) => <Button key={String(type)} asChild size="sm" variant="outline" className="rounded-lg"><a href={`./settle_batch.php?type=${type}&batch=${encodeURIComponent(batch)}`}>批量转账 · {label}</a></Button>)
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-[1200px] flex-col gap-6"><header className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "批量结算"}</h1><p className="mt-1 text-sm text-muted-foreground">{config.description ?? "生成、导出并处理结算批次。"}</p></div><Button className="rounded-xl" disabled={pending !== ""} onClick={() => void generate()}>{pending === "generate" ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <RefreshCw data-icon="inline-start" />}生成结算批次</Button></header>{notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "操作失败" : "操作成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}<Card className="overflow-hidden rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle className="text-base">历史批次</CardTitle><CardDescription>生成批次会把所有待结算记录置为处理中。</CardDescription></CardHeader><CardContent className="p-0"><div className="overflow-x-auto"><Table className="min-w-[980px]"><TableHeader><TableRow><TableHead>批次号</TableHead><TableHead>总金额</TableHead><TableHead>总数量</TableHead><TableHead>生成时间</TableHead><TableHead>状态</TableHead><TableHead className="text-right">操作</TableHead></TableRow></TableHeader><TableBody>{rows.length ? rows.map((row) => { const batch = String(row.batch ?? ""); const done = Number(row.status) === 1; return <TableRow key={batch}><TableCell className="font-mono text-xs">{batch}</TableCell><TableCell>{String(row.allmoney ?? "—")}</TableCell><TableCell>{String(row.count ?? "—")}</TableCell><TableCell>{String(row.time ?? "—")}</TableCell><TableCell>{done ? "已完成" : "待处理"}</TableCell><TableCell><div className="flex flex-wrap justify-end gap-2"><Button asChild size="sm" variant="outline" className="rounded-lg"><a href={`./slist.php?batch=${encodeURIComponent(batch)}`}>结算列表</a></Button><Button asChild size="sm" variant="outline" className="rounded-lg"><a href={`./download.php?act=settle&batch=${encodeURIComponent(batch)}`}>导出汇总</a></Button>{transferLinks(batch)}{!done ? <Button size="sm" variant="outline" className="rounded-lg" disabled={pending !== ""} onClick={() => setConfirm(row)}>标记完成</Button> : null}</div></TableCell></TableRow> }) : <TableRow><TableCell colSpan={6} className="h-32 text-center text-muted-foreground">暂无结算批次</TableCell></TableRow>}</TableBody></Table></div></CardContent></Card><Dialog open={Boolean(confirm)} onOpenChange={(open) => { if (!open) setConfirm(null) }}><DialogContent><DialogHeader><DialogTitle>确认标记完成？</DialogTitle><DialogDescription>批次 {String(confirm?.batch ?? "")} 的所有结算记录将被标记为已完成。</DialogDescription></DialogHeader><DialogFooter><Button variant="outline" onClick={() => setConfirm(null)}>取消</Button><Button onClick={() => { if (confirm) void complete(confirm) }}>确认操作</Button></DialogFooter></DialogContent></Dialog></div></div>
}

type RollItem = { channel: string; weight: string }

export function AdminRollConfigView({ config = {} }: { config?: AdminRollConfig }) {
  const [channels, setChannels] = React.useState<TokenChannel[]>([])
  const [items, setItems] = React.useState<RollItem[]>([])
  const [kind, setKind] = React.useState(0)
  const [loading, setLoading] = React.useState(true)
  const [pending, setPending] = React.useState(false)
  const [notice, setNotice] = React.useState<AccountNotice | null>(null)
  const load = React.useCallback(async () => {
    setLoading(true); setNotice(null)
    try {
      const response = await fetch(`ajax_pay.php?act=rollInfo&id=${encodeURIComponent(String(config.rollId ?? ""))}`, { credentials: "same-origin" })
      const data = await readResponse(response)
      const rawChannels = Array.isArray(data.channels) ? data.channels as JsonObject[] : []
      setChannels(rawChannels.map((row) => ({ value: String(row.id ?? ""), label: `${row.id ?? ""} - ${row.name ?? ""}` })))
      setKind(Number(data.kind ?? 0))
      const rawItems = Array.isArray(data.info) ? data.info as JsonObject[] : []
      setItems(rawItems.map((item) => ({ channel: String(item.channel ?? ""), weight: String(item.weight ?? "1") })))
    } catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "加载轮询配置失败" }) } finally { setLoading(false) }
  }, [config.rollId])
  React.useEffect(() => { const timer = window.setTimeout(() => void load(), 0); return () => window.clearTimeout(timer) }, [load])
  const update = (index: number, key: keyof RollItem, value: string) => setItems((current) => current.map((item, itemIndex) => itemIndex === index ? { ...item, [key]: value } : item))
  const save = async () => {
    if (!items.length) { setNotice({ kind: "error", text: "至少配置一个支付通道" }); return }
    setPending(true); setNotice(null)
    try {
      const body = new URLSearchParams({ csrf_token: String(config.csrf_token ?? "") })
      items.forEach((item, index) => { body.set(`list[${index}][channel]`, item.channel); body.set(`list[${index}][weight]`, item.weight || "1") })
      const response = await fetch(`ajax_pay.php?act=saveRollInfo&id=${encodeURIComponent(String(config.rollId ?? ""))}`, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      const data = await readResponse(response)
      setNotice({ kind: "success", text: String(data.msg ?? "轮询通道配置已保存") })
    } catch (error) { setNotice({ kind: "error", text: error instanceof Error ? error.message : "保存失败" }) } finally { setPending(false) }
  }
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-3xl flex-col gap-6"><header><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "配置轮询通道"}</h1><p className="mt-1 text-sm text-muted-foreground">{config.description ?? "选择该轮询组要使用的支付通道。"}</p></header>{notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "操作失败" : "操作成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}<Card className="rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle>轮询通道</CardTitle><CardDescription>{kind === 1 ? "权重随机轮询会按权重分配请求。" : "通道顺序将作为轮询顺序。"}</CardDescription></CardHeader><CardContent className="flex flex-col gap-4 pt-6">{loading ? <p className="text-sm text-muted-foreground">正在加载通道…</p> : items.length ? items.map((item, index) => <div key={`${index}-${item.channel}`} className="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-end"><Field className="min-w-0 flex-1"><FieldLabel>支付通道</FieldLabel><Select value={item.channel || "__empty"} onValueChange={(value) => update(index, "channel", value === "__empty" ? "" : value)}><SelectTrigger><SelectValue placeholder="选择通道" /></SelectTrigger><SelectContent><SelectGroup>{channels.map((channel) => <SelectItem key={channel.value} value={channel.value}>{channel.label}</SelectItem>)}</SelectGroup></SelectContent></Select></Field><Field className="sm:w-36"><FieldLabel>权重</FieldLabel><Input type="number" min="1" value={item.weight} onChange={(event) => update(index, "weight", event.target.value)} disabled={kind !== 1} /></Field><Button type="button" variant="outline" className="rounded-xl" onClick={() => setItems((current) => current.filter((_, itemIndex) => itemIndex !== index))}>移除</Button></div>) : <p className="text-sm text-muted-foreground">暂无已配置通道，请添加一个。</p>}<Button type="button" variant="outline" className="w-fit rounded-xl" disabled={!channels.length} onClick={() => setItems((current) => [...current, { channel: channels[0]?.value ?? "", weight: "1" }])}>添加通道</Button></CardContent><CardFooter className="flex flex-wrap gap-2 border-t"><Button className="rounded-xl" disabled={loading || pending || !items.length} onClick={() => void save()}>{pending ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <Save data-icon="inline-start" />}保存配置</Button><Button type="button" variant="outline" className="rounded-xl" onClick={() => void load()}><RefreshCw data-icon="inline-start" />重新加载</Button><Button asChild type="button" variant="outline" className="rounded-xl"><a href="./pay_roll.php">返回轮询列表</a></Button></CardFooter></Card></div></div>
}

export function AdminMaintenanceView({ config = {} }: { config?: AdminMaintenanceConfig }) {
  const [confirm, setConfirm] = React.useState<MaintenanceAction | null>(null)
  const [loading, setLoading] = React.useState("")
  const [notice, setNotice] = React.useState("")
  const run = async (action: MaintenanceAction) => {
    setLoading(action.endpoint); setNotice("")
    try {
      const method = action.method ?? "GET"
      const body = method === "POST" ? new URLSearchParams({ csrf_token: String(config.csrf_token ?? "") }) : undefined
      const response = await fetch(action.endpoint, { method, credentials: "same-origin", headers: body ? { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" } : undefined, body })
      const data = (await response.json().catch(() => ({}))) as JsonObject
      if (!response.ok || (data.code !== undefined && ![0, 200].includes(Number(data.code)))) throw new Error(String(data.msg ?? "操作失败"))
      setNotice(String(data.msg ?? "操作成功"))
    } catch (error) { setNotice(error instanceof Error ? error.message : "操作失败") } finally { setLoading("") }
  }
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-4xl flex-col gap-6"><header><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "系统维护"}</h1><p className="mt-1 text-sm text-muted-foreground">{config.description ?? "执行数据清理和缓存维护。"}</p></header>{notice ? <Alert><AlertTitle>执行结果</AlertTitle><AlertDescription>{notice}</AlertDescription></Alert> : null}<div className="grid gap-4 md:grid-cols-2">{(config.actions ?? []).map((action) => <Card key={action.endpoint} className="rounded-2xl shadow-sm"><CardHeader><CardTitle className="text-base">{action.label}</CardTitle><CardDescription>{action.description}</CardDescription></CardHeader><CardFooter><Button variant={action.destructive ? "destructive" : "outline"} className="rounded-xl" disabled={loading === action.endpoint} onClick={() => action.destructive ? setConfirm(action) : void run(action)}>{loading === action.endpoint ? <Loader2 data-icon="inline-start" className="animate-spin" /> : action.destructive ? <Trash2 data-icon="inline-start" /> : <RefreshCw data-icon="inline-start" />}{action.label}</Button></CardFooter></Card>)}</div><Dialog open={Boolean(confirm)} onOpenChange={(open) => { if (!open) setConfirm(null) }}><DialogContent><DialogHeader><DialogTitle>确认执行？</DialogTitle><DialogDescription>{confirm?.description}删除操作不可恢复，请确认数据备份已经完成。</DialogDescription></DialogHeader><DialogFooter><Button variant="outline" onClick={() => setConfirm(null)}>取消</Button><Button variant="destructive" onClick={() => { if (!confirm) return; const action = confirm; setConfirm(null); void run(action) }}>确认执行</Button></DialogFooter></DialogContent></Dialog></div></div>
}

export function AdminSettlementBatchView({ config = {} }: { config?: AdminSettlementBatchConfig }) {
  const [channel, setChannel] = React.useState(String(config.channels?.[0]?.value ?? ""))
  const [paypwd, setPaypwd] = React.useState("")
  const [rows, setRows] = React.useState<JsonObject[]>([...(config.rows ?? [])])
  const [loading, setLoading] = React.useState("")
  const [notice, setNotice] = React.useState("")
  const transfer = async (id: string) => {
    setLoading(id); setNotice("")
    try {
      const body = new URLSearchParams({ csrf_token: String(config.csrf_token ?? ""), id, type: String(config.type ?? 1), channel, paypwd })
      const response = await fetch("ajax_settle.php?act=transfer", { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      const data = (await response.json().catch(() => ({}))) as JsonObject
      if (!response.ok || Number(data.code) !== 0) throw new Error(String(data.msg ?? "转账失败"))
      setRows((current) => current.map((row) => String(row.id) === id ? { ...row, transfer_status: 1, transfer_result: String(data.result ?? "已完成") } : row))
      setNotice(String(data.result ?? "转账处理完成"))
    } catch (error) { setNotice(error instanceof Error ? error.message : "转账失败") } finally { setLoading("") }
  }
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-[1440px] flex-col gap-6"><header><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "批量结算"}</h1><p className="mt-1 text-sm text-muted-foreground">{config.description ?? "逐笔处理待结算记录。"}</p></header><Card className="rounded-2xl shadow-sm"><CardContent className="pt-6"><FieldGroup className="grid gap-5 sm:grid-cols-2"><Field><FieldLabel>转账通道</FieldLabel><Select value={channel || "__empty"} onValueChange={(value) => setChannel(value === "__empty" ? "" : value)}><SelectTrigger><SelectValue placeholder="选择通道" /></SelectTrigger><SelectContent><SelectGroup>{(config.channels ?? []).map((item) => <SelectItem key={item.value} value={item.value}>{item.label}</SelectItem>)}</SelectGroup></SelectContent></Select></Field><Field><FieldLabel>支付密码</FieldLabel><Input type="password" value={paypwd} onChange={(event) => setPaypwd(event.target.value)} /></Field></FieldGroup>{notice ? <Alert className="mt-5"><AlertTitle>处理结果</AlertTitle><AlertDescription>{notice}</AlertDescription></Alert> : null}</CardContent></Card><Card className="overflow-hidden rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle className="text-base">待处理结算</CardTitle><CardDescription>共 {rows.length} 条记录，逐笔点击转账可追踪结果。</CardDescription></CardHeader><CardContent className="p-0"><div className="overflow-x-auto"><Table><TableHeader><TableRow><TableHead>ID</TableHead><TableHead>商户</TableHead><TableHead>收款账号</TableHead><TableHead>姓名</TableHead><TableHead>金额</TableHead><TableHead>结果</TableHead><TableHead className="text-right">操作</TableHead></TableRow></TableHeader><TableBody>{rows.length ? rows.map((row) => { const id = String(row.id ?? ""); const done = Number(row.transfer_status) === 1; return <TableRow key={id}><TableCell className="font-mono text-xs">{id}</TableCell><TableCell>{String(row.uid ?? "—")}</TableCell><TableCell>{String(row.account ?? "—")}</TableCell><TableCell>{String(row.username ?? "—")}</TableCell><TableCell>{String(row.realmoney ?? row.money ?? "—")}</TableCell><TableCell>{String(row.transfer_result ?? (done ? "已完成" : "待处理"))}</TableCell><TableCell className="text-right"><Button size="sm" variant={done ? "outline" : "default"} className="rounded-lg" disabled={done || !channel || !paypwd || loading === id} onClick={() => void transfer(id)}>{loading === id ? <Loader2 data-icon="inline-start" className="animate-spin" /> : null}{done ? "已完成" : "立即转账"}</Button></TableCell></TableRow> }) : <TableRow><TableCell colSpan={7} className="h-32 text-center text-muted-foreground">暂无待处理记录</TableCell></TableRow>}</TableBody></Table></div></CardContent></Card></div></div>
}
