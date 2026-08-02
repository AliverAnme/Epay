import * as React from "react"
import { Check, Loader2, RefreshCw, Save } from "lucide-react"

import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
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
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
  FieldSet,
  FieldLegend,
} from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Switch } from "@/components/ui/switch"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Textarea } from "@/components/ui/textarea"

type JsonObject = Record<string, unknown>

export type AdminFormOption = { value: string; label: string }
export type AdminFormField = {
  key: string
  label: string
  type?: "text" | "number" | "password" | "date" | "datetime-local" | "textarea" | "select" | "switch" | "file" | "hidden"
  value?: string | number | boolean | null
  placeholder?: string
  description?: string
  required?: boolean
  readOnly?: boolean
  options?: readonly AdminFormOption[]
  accept?: string
  className?: string
}
export type AdminFormSection = {
  title: string
  description?: string
  fields: readonly AdminFormField[]
}
export type AdminFormAction = {
  endpoint: string
  method?: "GET" | "POST"
  submitMode?: "native" | "fetch"
  reloadOnSuccess?: boolean
  submitLabel?: string
}
export type AdminFormConfig = {
  title?: string
  description?: string
  sitename?: string
  csrf_token?: string
  action?: AdminFormAction
  fields?: readonly AdminFormField[]
  sections?: readonly AdminFormSection[]
  values?: JsonObject
  submitLabel?: string
  resetLabel?: string
  notice?: string
  links?: readonly { label: string; href: string }[]
}

type Notice = { kind: "success" | "error"; text: string }

function valueFor(field: AdminFormField, values: Record<string, string>) {
  const value = values[field.key]
  if (value !== undefined) return value
  if (typeof field.value === "boolean") return field.value ? "1" : "0"
  return field.value === undefined || field.value === null ? "" : String(field.value)
}

async function readResponse(response: Response) {
  const data = (await response.json().catch(() => ({}))) as JsonObject
  if (!response.ok || (data.code !== undefined && ![0, 200].includes(Number(data.code)))) {
    throw new Error(String(data.msg ?? "请求失败"))
  }
  return data
}

function Control({ field, value, onChange, id: providedId, name: providedName }: { field: AdminFormField; value: string; onChange: (value: string) => void; id?: string; name?: string }) {
  const id = providedId ?? `admin-form-${field.key.replace(/[^a-zA-Z0-9_-]/g, "-")}`
  const name = providedName ?? field.key
  if (field.type === "hidden") return <input id={id} type="hidden" name={name} value={value} />
  if (field.type === "textarea") {
    return <Textarea id={id} name={name} value={value} onChange={(event) => onChange(event.target.value)} placeholder={field.placeholder} required={field.required} readOnly={field.readOnly} />
  }
  if (field.type === "select") {
    return <>
      <Select value={value || "__empty"} onValueChange={(next) => onChange(next === "__empty" ? "" : next)}>
        <SelectTrigger id={id} className="w-full"><SelectValue placeholder={field.placeholder ?? field.label} /></SelectTrigger>
        <SelectContent><SelectGroup>{(field.options ?? []).map((option) => <SelectItem key={option.value || "__empty"} value={option.value || "__empty"}>{option.label}</SelectItem>)}</SelectGroup></SelectContent>
      </Select>
      <input type="hidden" name={name} value={value} />
    </>
  }
  if (field.type === "switch") {
    return <>
      <div className="flex min-h-10 items-center"><Switch id={id} checked={value === "1" || value === "true"} onCheckedChange={(checked) => onChange(checked ? "1" : "0")} /></div>
      <input type="hidden" name={name} value={value === "1" || value === "true" ? "1" : "0"} />
    </>
  }
  return <Input id={id} name={name} type={field.type ?? "text"} {...(field.type === "file" ? {} : { value })} onChange={(event) => onChange(event.target.value)} placeholder={field.placeholder} required={field.required} readOnly={field.readOnly} accept={field.accept} />
}

export function AdminFormView({ config = {} }: { config?: AdminFormConfig }) {
  const fields = React.useMemo(() => config.fields ?? config.sections?.flatMap((section) => section.fields) ?? [], [config.fields, config.sections])
  const initial = React.useMemo(() => Object.fromEntries(fields.map((field) => [field.key, valueFor(field, config.values ? Object.fromEntries(Object.entries(config.values).map(([key, value]) => [key, String(value ?? "")])) : {})])), [config.values, fields])
  const [values, setValues] = React.useState<Record<string, string>>(initial)
  const [pending, setPending] = React.useState(false)
  const [notice, setNotice] = React.useState<Notice | null>(null)
  const title = String(config.title ?? "后台配置")
  const description = String(config.description ?? "使用统一的 shadcn 表单维护平台配置。")
  const sitename = String(config.sitename ?? "Rainbow Pay")
  const action = config.action
  const submitMode = action?.submitMode ?? "native"
  const update = (key: string, value: string) => setValues((current) => ({ ...current, [key]: value }))
  const reset = () => setValues(initial)

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    if (!action || submitMode !== "fetch") return
    event.preventDefault()
    setPending(true)
    setNotice(null)
    try {
      const body = new URLSearchParams()
      body.set("csrf_token", String(config.csrf_token ?? ""))
      Object.entries(values).forEach(([key, value]) => body.set(key, value))
      const response = await fetch(action.endpoint, { method: action.method ?? "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      const data = await readResponse(response)
      setNotice({ kind: "success", text: String(data.msg ?? "保存成功") })
      if (action.reloadOnSuccess) window.setTimeout(() => window.location.reload(), 500)
    } catch (error) {
      setNotice({ kind: "error", text: error instanceof Error ? error.message : "保存失败" })
    } finally {
      setPending(false)
    }
  }

  const renderFields = (items: readonly AdminFormField[]) => <FieldGroup className="grid gap-5 md:grid-cols-2">{items.map((field) => <Field key={field.key} className={field.className}>
    <FieldLabel htmlFor={`admin-form-${field.key.replace(/[^a-zA-Z0-9_-]/g, "-")}`}>{field.label}{field.required ? <span aria-hidden="true"> *</span> : null}</FieldLabel>
    <Control field={field} value={valueFor(field, values)} onChange={(value) => update(field.key, value)} />
    {field.description ? <FieldDescription>{field.description}</FieldDescription> : null}
  </Field>)}</FieldGroup>

  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <header className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs text-muted-foreground">{sitename}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{title}</h1><p className="mt-1 max-w-3xl text-sm text-muted-foreground">{description}</p></div>{config.links?.length ? <div className="flex flex-wrap gap-2">{config.links.map((link) => <Button key={link.href} asChild variant="outline" className="rounded-xl"><a href={link.href}>{link.label}</a></Button>)}</div> : null}</header>
    {config.notice ? <Alert><AlertTitle>说明</AlertTitle><AlertDescription>{config.notice}</AlertDescription></Alert> : null}
    {notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "保存失败" : "保存成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}
    <Card className="rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle>{title}</CardTitle><CardDescription>{description}</CardDescription></CardHeader><form action={action?.endpoint} method={action?.method ?? "POST"} encType={fields.some((field) => field.type === "file") ? "multipart/form-data" : undefined} onSubmit={submit}>
      <CardContent className="flex flex-col gap-8 pt-6">{config.sections?.length ? config.sections.map((section) => <FieldSet key={section.title}><FieldLegend>{section.title}</FieldLegend>{section.description ? <FieldDescription>{section.description}</FieldDescription> : null}{renderFields(section.fields)}</FieldSet>) : renderFields(fields)}<input type="hidden" name="csrf_token" value={config.csrf_token ?? ""} />
      </CardContent><CardFooter className="flex flex-wrap gap-2 border-t">{action ? <Button type="submit" className="rounded-xl" disabled={pending}>{pending ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <Save data-icon="inline-start" />}{action.submitLabel ?? config.submitLabel ?? "保存"}</Button> : null}<Button type="button" variant="outline" className="rounded-xl" onClick={reset} disabled={pending}><RefreshCw data-icon="inline-start" />{config.resetLabel ?? "重置"}</Button></CardFooter>
    </form></Card>
  </div></div>
}

export function AdminStatsView({ config = {} }: { config?: AdminFormConfig & { endpoint?: string; columns?: readonly { key: string; label: string }[]; response?: "array" | "dynamic" } }) {
  const fields = React.useMemo(() => config.fields ?? [], [config.fields])
  const initial = React.useMemo(() => Object.fromEntries(fields.map((field) => [field.key, valueFor(field, {})])), [fields])
  const [values, setValues] = React.useState<Record<string, string>>(initial)
  const [rows, setRows] = React.useState<JsonObject[]>([])
  const [columns, setColumns] = React.useState<readonly { key: string; label: string }[]>(config.columns ?? [])
  const [loading, setLoading] = React.useState(false)
  const [error, setError] = React.useState("")
  const endpoint = String(config.endpoint ?? "")
  const update = (key: string, value: string) => setValues((current) => ({ ...current, [key]: value }))
  const load = async (event?: React.FormEvent) => {
    event?.preventDefault()
    setLoading(true); setError("")
    try {
      const body = new URLSearchParams({ csrf_token: String(config.csrf_token ?? "") })
      Object.entries(values).forEach(([key, value]) => body.set(key, value))
      const response = await fetch(endpoint, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      const data = await readResponse(response)
      const nextRows = Array.isArray(data) ? data as JsonObject[] : Array.isArray(data.data) ? data.data as JsonObject[] : []
      if (data.columns && typeof data.columns === "object") setColumns(Object.entries(data.columns as JsonObject).map(([key, label]) => ({ key, label: String(label) })))
      setRows(nextRows)
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "查询失败")
    } finally { setLoading(false) }
  }
  return <div className="min-w-0 w-full"><div className="mx-auto flex w-full max-w-[1440px] flex-col gap-6"><header><p className="text-xs text-muted-foreground">{config.sitename ?? "Rainbow Pay"}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight">{config.title ?? "数据统计"}</h1><p className="mt-1 text-sm text-muted-foreground">{config.description ?? "按条件查询统计数据。"}</p></header><Card className="rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle className="text-base">查询条件</CardTitle><CardDescription>选择时间和维度后加载统计结果。</CardDescription></CardHeader><CardContent className="pt-6"><form onSubmit={load}><FieldGroup className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">{fields.map((field) => <Field key={field.key}><FieldLabel htmlFor={`stats-${field.key}`}>{field.label}</FieldLabel><Control field={field} id={`stats-${field.key}`} name={field.key} value={values[field.key] ?? ""} onChange={(value) => update(field.key, value)} /></Field>)}</FieldGroup><div className="mt-5 flex gap-2"><Button type="submit" className="rounded-xl" disabled={loading}>{loading ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <Check data-icon="inline-start" />}立即查询</Button><Button type="button" variant="outline" className="rounded-xl" onClick={() => { setValues(initial); setRows([]) }}><RefreshCw data-icon="inline-start" />重置</Button></div></form></CardContent></Card><Card className="overflow-hidden rounded-2xl shadow-sm"><CardHeader className="border-b"><CardTitle className="text-base">统计结果</CardTitle><CardDescription>{rows.length ? `共 ${rows.length} 条记录` : "尚未加载数据"}</CardDescription></CardHeader><CardContent className="p-0">{error ? <Alert variant="destructive" className="m-6"><AlertTitle>查询失败</AlertTitle><AlertDescription>{error}</AlertDescription></Alert> : <div className="overflow-x-auto"><Table><TableHeader><TableRow>{columns.map((column) => <TableHead key={column.key}>{column.label}</TableHead>)}</TableRow></TableHeader><TableBody>{rows.length ? rows.map((row, index) => <TableRow key={String(row.uid ?? row.user ?? row.account ?? index)}>{columns.map((column) => <TableCell key={column.key}>{String(row[column.key] ?? "—")}</TableCell>)}</TableRow>) : <TableRow><TableCell colSpan={Math.max(columns.length, 1)} className="h-32 text-center text-muted-foreground">暂无统计结果</TableCell></TableRow>}</TableBody></Table></div>}</CardContent></Card></div></div>
}
