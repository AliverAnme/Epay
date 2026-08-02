import * as React from "react"
import {
  BarChart3,
  Check,
  ChevronLeft,
  ChevronRight,
  CircleDollarSign,
  Eye,
  FileText,
  Loader2,
  MoreHorizontal,
  RefreshCw,
  Search,
  ShieldCheck,
  Trash2,
  X,
} from "lucide-react"

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
import { Checkbox } from "@/components/ui/checkbox"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

type JsonObject = Record<string, unknown>

export type AdminOrderConfig = {
  sitename?: string
  csrf_token?: string
  paymentTypes?: Array<{ id: string | number; name?: string; showname?: string }>
}

type OrderRow = JsonObject & {
  trade_no?: string
  out_trade_no?: string
  uid?: string | number
  domain?: string
  name?: string
  money?: string | number
  realmoney?: string | number | null
  getmoney?: string | number | null
  profitmoney?: string | number | null
  type?: string | number
  typename?: string
  typeshowname?: string
  channel?: string | number
  channelname?: string
  plugin?: string
  ip?: string
  buyer?: string
  mobile?: string
  param?: string
  addtime?: string
  endtime?: string
  status?: string | number
  settle?: string | number
  notify?: string | number
  refundmoney?: string | number
  combine?: string | number
}

type Filters = {
  column: string
  value: string
  uid: string
  type: string
  channel: string
  starttime: string
  endtime: string
  dstatus: string
}

type Notice = { kind: "success" | "error"; text: string }
type ConfirmState = {
  title: string
  description: string
  confirmLabel?: string
  destructive?: boolean
  onConfirm: () => void | Promise<void>
}
type RefundState = {
  row: OrderRow
  api: boolean
  money: string
  paypwd: string
}
type BatchRefundState = {
  rows: OrderRow[]
  money: Record<string, string>
  paypwd: string
}

const DEFAULT_FILTERS: Filters = {
  column: "trade_no",
  value: "",
  uid: "",
  type: "",
  channel: "",
  starttime: "",
  endtime: "",
  dstatus: "",
}

const SEARCH_COLUMNS = [
  ["trade_no", "订单号"],
  ["out_trade_no", "商户订单号"],
  ["api_trade_no", "接口订单号"],
  ["bill_mch_trade_no", "渠道交易单号"],
  ["bill_trade_no", "用户交易单号"],
  ["name", "商品名称"],
  ["money", "订单金额"],
  ["realmoney", "实付金额"],
  ["getmoney", "分成金额"],
  ["domain", "网站域名"],
  ["buyer", "支付账号"],
  ["ip", "支付 IP"],
  ["mobile", "手机号码"],
  ["param", "扩展参数"],
] as const

const STATUS_OPTIONS = [
  ["0", "未支付"],
  ["1", "已支付"],
  ["2", "已退款"],
  ["3", "已冻结"],
  ["4", "预授权"],
  ["settle_1", "待结算"],
  ["settle_2", "结算成功"],
  ["settle_3", "结算失败"],
] as const

const BATCH_LABELS: Record<string, string> = {
  "0": "改未完成",
  "1": "改已完成",
  "2": "冻结订单",
  "3": "解冻订单",
  "4": "删除订单",
  "5": "API 退款",
  "6": "确认结算",
  "7": "重新通知",
}

function stringValue(value: unknown, fallback = "") {
  if (value === null || value === undefined) return fallback
  return String(value)
}

function numberValue(value: unknown) {
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : 0
}

function readInitialState() {
  if (typeof window === "undefined") {
    return { filters: DEFAULT_FILTERS, page: 1, pageSize: 30 }
  }
  const params = new URLSearchParams(window.location.search)
  const filters = { ...DEFAULT_FILTERS }
  Object.keys(filters).forEach((key) => {
    filters[key as keyof Filters] = params.get(key) ?? filters[key as keyof Filters]
  })
  const page = Math.max(1, Number(params.get("pageNumber")) || 1)
  const pageSize = [10, 30, 50, 100].includes(Number(params.get("pageSize")))
    ? Number(params.get("pageSize"))
    : 30
  return { filters, page, pageSize }
}

function statusLabel(row: OrderRow) {
  const status = numberValue(row.status)
  if (status === 1) return "已支付"
  if (status === 2) return "已退款"
  if (status === 3) return "已冻结"
  if (status === 4) return "预授权"
  return "未支付"
}

function statusBadge(row: OrderRow) {
  const status = numberValue(row.status)
  const refund = numberValue(row.refundmoney)
  const partialRefund = status === 2 && refund > 0 && refund < numberValue(row.realmoney)
  return (
    <div className="flex flex-col items-start gap-1">
      <Badge variant={status === 1 ? "secondary" : "outline"} className="rounded-md font-normal">
        {statusLabel(row)}
      </Badge>
      {partialRefund && <span className="text-xs text-muted-foreground">退款 ¥ {refund.toFixed(2)}</span>}
      {row.plugin === "alipayd" || row.plugin === "wxpaynp" || row.plugin === "alipayrp" ? (
        row.settle === 1 || row.settle === "1" ? <Badge variant="outline" className="rounded-md font-normal">待处理</Badge> :
        row.settle === 2 || row.settle === "2" ? <Badge variant="secondary" className="rounded-md font-normal">已完成</Badge> :
        row.settle === 3 || row.settle === "3" ? <Badge variant="outline" className="rounded-md font-normal">处理失败</Badge> : null
      ) : null}
    </div>
  )
}

function typeValue(value: string) {
  return value === "all" ? "" : value
}

function displayType(row: OrderRow) {
  const name = stringValue(row.typeshowname || row.typename, "未知方式")
  const channel = row.channel ? `通道 ${row.channel}` : ""
  return channel ? `${name} · ${channel}` : name
}

function money(value: unknown) {
  const number = Number(value)
  return Number.isFinite(number) ? `¥ ${number.toFixed(2)}` : "—"
}

async function decodeResponse(response: Response) {
  const data = (await response.json().catch(() => ({}))) as JsonObject
  if (!response.ok) throw new Error(stringValue(data.msg, "请求失败"))
  return data
}

function ConfirmDialog({
  state,
  open,
  onOpenChange,
  busy,
}: {
  state: ConfirmState | null
  open: boolean
  onOpenChange: (open: boolean) => void
  busy: boolean
}) {
  if (!state) return null
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{state.title}</DialogTitle>
          <DialogDescription>{state.description}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={busy}>
            取消
          </Button>
          <Button
            variant={state.destructive ? "destructive" : "default"}
            onClick={() => void state.onConfirm()}
            disabled={busy}
          >
            {busy ? <Loader2 className="animate-spin" data-icon="inline-start" /> : null}
            {state.confirmLabel ?? "确认"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

export function AdminOrderView({ config = {} }: { config?: AdminOrderConfig }) {
  const initial = React.useMemo(() => readInitialState(), [])
  const [filters, setFilters] = React.useState<Filters>(initial.filters)
  const [applied, setApplied] = React.useState<Filters>(initial.filters)
  const [page, setPage] = React.useState(initial.page)
  const [pageSize, setPageSize] = React.useState(initial.pageSize)
  const [rows, setRows] = React.useState<OrderRow[]>([])
  const [total, setTotal] = React.useState(0)
  const [selected, setSelected] = React.useState<Set<string>>(new Set())
  const [loading, setLoading] = React.useState(true)
  const [notice, setNotice] = React.useState<Notice | null>(null)
  const [error, setError] = React.useState("")
  const [statisticsOpen, setStatisticsOpen] = React.useState(false)
  const [statistics, setStatistics] = React.useState<JsonObject | null>(null)
  const [statisticsLoading, setStatisticsLoading] = React.useState(false)
  const [detailOpen, setDetailOpen] = React.useState(false)
  const [detailLoading, setDetailLoading] = React.useState(false)
  const [detail, setDetail] = React.useState<OrderRow | null>(null)
  const [subOrders, setSubOrders] = React.useState<OrderRow[]>([])
  const [refund, setRefund] = React.useState<RefundState | null>(null)
  const [batchRefund, setBatchRefund] = React.useState<BatchRefundState | null>(null)
  const [confirm, setConfirm] = React.useState<ConfirmState | null>(null)
  const [busy, setBusy] = React.useState(false)

  const paymentTypes = config.paymentTypes ?? []
  const csrfToken = stringValue(config.csrf_token)
  const sitename = stringValue(config.sitename, "Rainbow Pay")
  const totalPages = Math.max(1, Math.ceil(total / pageSize))
  const allSelected = rows.length > 0 && rows.every((row) => selected.has(stringValue(row.trade_no)))
  const someSelected = rows.some((row) => selected.has(stringValue(row.trade_no)))

  const syncUrl = React.useCallback((nextFilters: Filters, nextPage: number, nextPageSize: number) => {
    if (typeof window === "undefined") return
    const url = new URL(window.location.href)
    Object.entries(nextFilters).forEach(([key, value]) => {
      if (value) url.searchParams.set(key, value)
      else url.searchParams.delete(key)
    })
    url.searchParams.set("pageNumber", String(nextPage))
    url.searchParams.set("pageSize", String(nextPageSize))
    window.history.replaceState({}, "", url)
  }, [])

  const buildBody = React.useCallback((source: Filters, offset: number, limit: number) => {
    const body = new URLSearchParams()
    body.set("csrf_token", csrfToken)
    Object.entries(source).forEach(([key, value]) => {
      if (value) body.set(key, value)
    })
    body.set("offset", String(offset))
    body.set("limit", String(limit))
    return body
  }, [csrfToken])

  const loadOrders = React.useCallback(async () => {
    setLoading(true)
    setError("")
    try {
      const response = await fetch("ajax_order.php?act=orderList", {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
        body: buildBody(applied, (page - 1) * pageSize, pageSize),
      })
      const data = await decodeResponse(response)
      setRows(Array.isArray(data.rows) ? (data.rows as OrderRow[]) : [])
      setTotal(Number(data.total) || 0)
      setSelected(new Set())
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "订单列表加载失败")
    } finally {
      setLoading(false)
    }
  }, [applied, buildBody, page, pageSize])

  React.useEffect(() => {
    const timer = window.setTimeout(() => void loadOrders(), 0)
    return () => window.clearTimeout(timer)
  }, [loadOrders])

  const post = React.useCallback(async (act: string, values: Record<string, string | number | string[]>) => {
    const body = new URLSearchParams()
    body.set("csrf_token", csrfToken)
    Object.entries(values).forEach(([key, value]) => {
      if (Array.isArray(value)) value.forEach((item) => body.append(`${key}[]`, item))
      else body.set(key, String(value))
    })
    const response = await fetch(`ajax_order.php?act=${encodeURIComponent(act)}`, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
      body,
    })
    return decodeResponse(response)
  }, [csrfToken])

  const announce = React.useCallback((kind: Notice["kind"], text: string) => {
    setNotice({ kind, text })
    window.setTimeout(() => setNotice(null), 4500)
  }, [])

  const runAction = React.useCallback(async (action: () => Promise<JsonObject>, successText?: string) => {
    setBusy(true)
    try {
      const data = await action()
      if (Number(data.code) !== 0 && Number(data.code) !== 200) throw new Error(stringValue(data.msg, "操作失败"))
      announce("success", successText ?? stringValue(data.msg, "操作成功"))
      await loadOrders()
      return data
    } catch (requestError) {
      announce("error", requestError instanceof Error ? requestError.message : "操作失败")
      return null
    } finally {
      setBusy(false)
    }
  }, [announce, loadOrders])

  const submitFilters = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setPage(1)
    setApplied({ ...filters })
    syncUrl(filters, 1, pageSize)
  }

  const clearFilters = () => {
    setFilters(DEFAULT_FILTERS)
    setApplied(DEFAULT_FILTERS)
    setPage(1)
    syncUrl(DEFAULT_FILTERS, 1, pageSize)
  }

  const changePage = (nextPage: number) => {
    const bounded = Math.min(Math.max(1, nextPage), totalPages)
    setPage(bounded)
    syncUrl(applied, bounded, pageSize)
  }

  const changePageSize = (value: string) => {
    const nextSize = Number(value)
    setPageSize(nextSize)
    setPage(1)
    syncUrl(applied, 1, nextSize)
  }

  const openStatistics = async () => {
    setStatisticsOpen(true)
    setStatisticsLoading(true)
    try {
      const response = await fetch("ajax_order.php?act=statistics", {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
        body: buildBody(applied, 0, 0),
      })
      const data = await decodeResponse(response)
      setStatistics((data.data as JsonObject) ?? null)
    } catch (requestError) {
      announce("error", requestError instanceof Error ? requestError.message : "统计加载失败")
    } finally {
      setStatisticsLoading(false)
    }
  }

  const openDetail = async (tradeNo: string) => {
    setDetailOpen(true)
    setDetailLoading(true)
    setDetail(null)
    setSubOrders([])
    try {
      const response = await fetch(`ajax_order.php?act=order&trade_no=${encodeURIComponent(tradeNo)}`, { credentials: "same-origin" })
      const data = await decodeResponse(response)
      setDetail((data.data as OrderRow) ?? null)
      if (numberValue(data.data && (data.data as OrderRow).combine) === 1) {
        const subResponse = await fetch(`ajax_order.php?act=subOrders&trade_no=${encodeURIComponent(tradeNo)}`, { credentials: "same-origin" })
        const subData = await decodeResponse(subResponse)
        setSubOrders(Array.isArray(subData.data) ? (subData.data as OrderRow[]) : [])
      }
    } catch (requestError) {
      announce("error", requestError instanceof Error ? requestError.message : "订单详情加载失败")
      setDetailOpen(false)
    } finally {
      setDetailLoading(false)
    }
  }

  const openRefund = async (row: OrderRow, api: boolean) => {
    setBusy(true)
    try {
      const data = await post("getmoney", { trade_no: stringValue(row.trade_no), ...(api ? { api: 1 } : {}) })
      if (Number(data.code) !== 0) throw new Error(stringValue(data.msg, "无法读取退款金额"))
      setRefund({ row, api, money: stringValue(data.money, stringValue(row.realmoney)), paypwd: "" })
    } catch (requestError) {
      announce("error", requestError instanceof Error ? requestError.message : "无法读取退款金额")
    } finally {
      setBusy(false)
    }
  }

  const submitRefund = async () => {
    if (!refund || !refund.money || (refund.api && !refund.paypwd)) return
    const current = refund
    setRefund(null)
    await runAction(
      () => post(current.api ? "apirefund" : "refund", {
        trade_no: stringValue(current.row.trade_no),
        money: current.money,
        ...(current.api ? { paypwd: current.paypwd } : {}),
      }),
      current.api ? "API 退款请求已提交" : "手动退款请求已提交"
    )
  }

  const openBatchRefund = async () => {
    const eligibleRows = rows.filter((row) => selected.has(stringValue(row.trade_no)))
    if (!eligibleRows.length) return
    setBusy(true)
    try {
      const amounts = await Promise.all(eligibleRows.map(async (row) => {
        const data = await post("getmoney", { trade_no: stringValue(row.trade_no), api: 1 })
        if (Number(data.code) !== 0) throw new Error(stringValue(data.msg, `无法读取订单 ${stringValue(row.trade_no)}`))
        return [stringValue(row.trade_no), stringValue(data.money, stringValue(row.realmoney ?? row.money))] as const
      }))
      setBatchRefund({ rows: eligibleRows, money: Object.fromEntries(amounts), paypwd: "" })
    } catch (requestError) {
      announce("error", requestError instanceof Error ? requestError.message : "无法读取批量退款金额")
    } finally {
      setBusy(false)
    }
  }

  const submitBatchRefund = async () => {
    if (!batchRefund || !batchRefund.paypwd) return
    const current = batchRefund
    setBatchRefund(null)
    await runAction(async () => {
      let last: JsonObject = { code: 0, msg: "" }
      for (const row of current.rows) {
        const tradeNo = stringValue(row.trade_no)
        const data = await post("apirefund", {
          trade_no: tradeNo,
          money: current.money[tradeNo] ?? stringValue(row.realmoney ?? row.money),
          paypwd: current.paypwd,
        })
        if (Number(data.code) !== 0) throw new Error(`${tradeNo}: ${stringValue(data.msg, "退款失败")}`)
        last = data
      }
      return last
    }, `已完成 ${current.rows.length} 条 API 退款`)
  }

  const selectedTradeNos = Array.from(selected)
  const batchCounts = React.useMemo(() => {
    const counts: Record<string, number> = Object.fromEntries(Object.keys(BATCH_LABELS).map((key) => [key, 0]))
    rows.forEach((row) => {
      if (!selected.has(stringValue(row.trade_no))) return
      const status = numberValue(row.status)
      if (status === 1) ["0", "2", "4", "5"].forEach((key) => counts[key]++)
      else if (status === 2) ["0", "1", "4"].forEach((key) => counts[key]++)
      else if (status === 3) ["3", "4", "5"].forEach((key) => counts[key]++)
      else ["1", "4"].forEach((key) => counts[key]++)
      if ((row.plugin === "alipayd" || row.plugin === "wxpaynp") && (row.settle === 1 || row.settle === 3 || row.settle === "1" || row.settle === "3")) counts["6"]++
      if (stringValue(row.notify, "0") !== "0") counts["7"]++
    })
    return counts
  }, [rows, selected])

  const executeBatch = async (action: string) => {
    if (!selectedTradeNos.length || !batchCounts[action]) return
    if (action === "5") {
      await openBatchRefund()
      return
    }
    if (action === "4") {
      setConfirm({
        title: "删除选中的订单？",
        description: `此操作将永久删除 ${selectedTradeNos.length} 条订单，无法撤销。`,
        confirmLabel: "确认删除",
        destructive: true,
        onConfirm: () => {
          setConfirm(null)
          void runAction(() => post("operation", { status: 4, checkbox: selectedTradeNos }), "批量删除已完成")
        },
      })
      return
    }
    if (action === "6" || action === "7") {
      const actionRows = rows.filter((row) => selected.has(stringValue(row.trade_no)))
      await runAction(async () => {
        let last: JsonObject = { code: 0, msg: "" }
        for (const row of actionRows) {
          const data = await post(action === "6" ? "alipaydSettle" : "notify", {
            trade_no: stringValue(row.trade_no),
            ...(action === "7" ? { isget: 1 } : {}),
          })
          if (Number(data.code) !== 0) throw new Error(`${stringValue(row.trade_no)}: ${stringValue(data.msg, "操作失败")}`)
          last = data
        }
        return last
      }, `批量${BATCH_LABELS[action]}已完成`)
      return
    }
    await runAction(() => post("operation", { status: Number(action), checkbox: selectedTradeNos }), `批量${BATCH_LABELS[action]}已完成`)
  }

  const rowAction = (row: OrderRow, action: string) => {
    const tradeNo = stringValue(row.trade_no)
    if (action === "detail") return void openDetail(tradeNo)
    if (action === "refund") return void openRefund(row, false)
    if (action === "apirefund") return void openRefund(row, true)
    if (action === "notify" || action === "return") {
      void runAction(async () => {
        const data = await post("notify", { trade_no: tradeNo, ...(action === "return" ? { isreturn: 1 } : {}) })
        if (data.url) window.open(stringValue(data.url), "_blank", "noopener,noreferrer")
        return data
      }, "通知地址已生成")
      return
    }
    if (action === "delete" || action === "fillorder") {
      setConfirm({
        title: action === "delete" ? "删除这条订单？" : "手动补单？",
        description: action === "delete" ? "订单删除后无法恢复。" : "该操作会将订单直接标记为已支付并触发分成，请确认订单状态。",
        confirmLabel: action === "delete" ? "确认删除" : "确认补单",
        destructive: action === "delete",
        onConfirm: () => {
          setConfirm(null)
          void runAction(
            () => action === "delete" ? fetch(`ajax_order.php?act=setStatus&trade_no=${encodeURIComponent(tradeNo)}&status=5`, { credentials: "same-origin" }).then(decodeResponse) : post("fillorder", { trade_no: tradeNo }),
            action === "delete" ? "订单已删除" : "补单成功"
          )
        },
      })
      return
    }
    if (action === "status0" || action === "status1") {
      void runAction(
        () => fetch(`ajax_order.php?act=setStatus&trade_no=${encodeURIComponent(tradeNo)}&status=${action === "status1" ? 1 : 0}`, { credentials: "same-origin" }).then(decodeResponse),
        "订单状态已更新"
      )
      return
    }
    if (["freeze", "unfreeze", "settle", "preauth", "unpreauth", "redpacket"].includes(action)) {
      const endpoint: Record<string, string> = { freeze: "freeze", unfreeze: "unfreeze", settle: "alipaydSettle", preauth: "alipayPreAuthPay", unpreauth: "alipayUnfreeze", redpacket: "alipayRedPacketTansfer" }
      void runAction(() => post(endpoint[action], { trade_no: tradeNo }), "订单操作已完成")
    }
  }

  const actionItems = (row: OrderRow) => {
    const status = numberValue(row.status)
    const items: Array<{ key: string; label: string; destructive?: boolean }> = []
    if ((row.plugin === "alipayd" || row.plugin === "wxpaynp") && [1, 3].includes(numberValue(row.settle))) items.push({ key: "settle", label: "确认结算" })
    if (row.plugin === "alipayrp" && [1, 3].includes(numberValue(row.settle))) items.push({ key: "redpacket", label: "红包转账重试" })
    if (status === 1) items.push({ key: "status0", label: "改未完成" }, { key: "apirefund", label: "API 退款" }, { key: "refund", label: "手动退款" }, { key: "freeze", label: "冻结订单" })
    else if (status === 2) items.push({ key: "status0", label: "改未完成" }, { key: "apirefund", label: "API 退款" }, { key: "status1", label: "改已完成" })
    else if (status === 3) items.push({ key: "unfreeze", label: "解冻订单" }, { key: "apirefund", label: "API 退款" })
    else {
      if (status === 4) items.push({ key: "preauth", label: "授权资金支付" }, { key: "unpreauth", label: "授权资金解冻" })
      items.push({ key: "status1", label: "改已完成" }, { key: "fillorder", label: "手动补单" })
    }
    items.push({ key: "notify", label: "重新通知（异步）" }, { key: "return", label: "重新通知（同步）" }, { key: "delete", label: "删除订单", destructive: true })
    return items
  }

  const detailEntries: Array<[string, unknown]> = detail ? [
    ["系统订单号", detail.trade_no],
    ["商户订单号", detail.out_trade_no],
    ["接口订单号", detail.api_trade_no],
    ["商户 ID", detail.uid],
    ["支付方式", detail.typename],
    ["支付通道", detail.channelname],
    ["商品名称", detail.name],
    ["订单金额", money(detail.money)],
    ["实际支付", money(detail.realmoney)],
    ["商户分成", money(detail.getmoney)],
    ["利润", money(detail.profitmoney)],
    ["创建时间", detail.addtime],
    ["完成时间", detail.endtime],
    ["支付账号", detail.buyer],
    ["手机号码", detail.mobile],
    ["网站域名", detail.domain],
    ["支付 IP", detail.ip],
    ["扩展参数", detail.param],
    ["订单状态", statusLabel(detail)],
  ] : []

  return (
    <div className="min-h-svh bg-muted/30 text-foreground">
      <div className="mx-auto flex w-full max-w-[1440px] flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <header className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex min-w-0 items-start gap-3">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl border bg-background">
              <CircleDollarSign data-icon="inline-start" />
            </div>
            <div className="min-w-0">
              <p className="text-xs text-muted-foreground">{sitename}</p>
              <h1 className="mt-1 text-2xl font-semibold tracking-tight">订单管理</h1>
              <p className="mt-1 text-sm text-muted-foreground">查询、核对并处理平台订单，所有操作都会记录在后台。</p>
            </div>
          </div>
          <Button variant="outline" onClick={() => void loadOrders()} disabled={loading} className="rounded-xl">
            {loading ? <Loader2 className="animate-spin" data-icon="inline-start" /> : <RefreshCw data-icon="inline-start" />}
            刷新列表
          </Button>
        </header>

        {notice && (
          <Alert variant={notice.kind === "error" ? "destructive" : "default"}>
            {notice.kind === "error" ? <X data-icon="inline-start" /> : <Check data-icon="inline-start" />}
            <AlertTitle>{notice.kind === "error" ? "操作未完成" : "操作成功"}</AlertTitle>
            <AlertDescription>{notice.text}</AlertDescription>
          </Alert>
        )}

        <Card className="rounded-2xl shadow-sm">
          <CardHeader className="gap-2 border-b">
            <CardTitle className="text-base">筛选订单</CardTitle>
            <CardDescription>支持订单号、商户、支付方式、状态与时间范围组合查询。</CardDescription>
          </CardHeader>
          <CardContent className="pt-6">
            <form onSubmit={submitFilters} className="flex flex-col gap-5">
              <FieldGroup className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-8">
                <Field>
                  <FieldLabel htmlFor="order-column">搜索字段</FieldLabel>
                  <Select value={filters.column} onValueChange={(value) => setFilters((current) => ({ ...current, column: value }))}>
                    <SelectTrigger id="order-column" className="w-full"><SelectValue placeholder="订单号" /></SelectTrigger>
                    <SelectContent><SelectGroup>{SEARCH_COLUMNS.map(([value, label]) => <SelectItem key={value} value={value}>{label}</SelectItem>)}</SelectGroup></SelectContent>
                  </Select>
                </Field>
                <Field className="sm:col-span-2">
                  <FieldLabel htmlFor="order-value">搜索内容</FieldLabel>
                  <Input id="order-value" value={filters.value} onChange={(event) => setFilters((current) => ({ ...current, value: event.target.value }))} placeholder="输入精确值；商品名支持模糊匹配" />
                </Field>
                <Field>
                  <FieldLabel htmlFor="order-uid">商户号</FieldLabel>
                  <Input id="order-uid" inputMode="numeric" value={filters.uid} onChange={(event) => setFilters((current) => ({ ...current, uid: event.target.value }))} placeholder="商户 ID" />
                </Field>
                <Field>
                  <FieldLabel htmlFor="order-type">支付方式</FieldLabel>
                  <Select value={filters.type || "all"} onValueChange={(value) => setFilters((current) => ({ ...current, type: typeValue(value) }))}>
                    <SelectTrigger id="order-type" className="w-full"><SelectValue placeholder="全部方式" /></SelectTrigger>
                    <SelectContent><SelectGroup><SelectItem value="all">全部方式</SelectItem>{paymentTypes.map((item) => <SelectItem key={String(item.id)} value={String(item.id)}>{item.showname || item.name || String(item.id)}</SelectItem>)}</SelectGroup></SelectContent>
                  </Select>
                </Field>
                <Field>
                  <FieldLabel htmlFor="order-channel">通道 ID</FieldLabel>
                  <Input id="order-channel" inputMode="numeric" value={filters.channel} onChange={(event) => setFilters((current) => ({ ...current, channel: event.target.value }))} placeholder="通道 ID" />
                </Field>
                <Field>
                  <FieldLabel htmlFor="order-starttime">开始日期</FieldLabel>
                  <Input id="order-starttime" type="date" value={filters.starttime} onChange={(event) => setFilters((current) => ({ ...current, starttime: event.target.value }))} />
                </Field>
                <Field>
                  <FieldLabel htmlFor="order-endtime">结束日期</FieldLabel>
                  <Input id="order-endtime" type="date" value={filters.endtime} onChange={(event) => setFilters((current) => ({ ...current, endtime: event.target.value }))} />
                </Field>
                <Field>
                  <FieldLabel htmlFor="order-status">订单状态</FieldLabel>
                  <Select value={filters.dstatus || "all"} onValueChange={(value) => setFilters((current) => ({ ...current, dstatus: typeValue(value) }))}>
                    <SelectTrigger id="order-status" className="w-full"><SelectValue placeholder="全部状态" /></SelectTrigger>
                    <SelectContent><SelectGroup><SelectItem value="all">全部状态</SelectItem>{STATUS_OPTIONS.map(([value, label]) => <SelectItem key={value} value={value}>{label}</SelectItem>)}</SelectGroup></SelectContent>
                  </Select>
                </Field>
              </FieldGroup>
              <div className="flex flex-wrap items-center gap-2">
                <Button type="submit" className="rounded-xl"><Search data-icon="inline-start" />搜索订单</Button>
                <Button type="button" variant="outline" onClick={clearFilters} className="rounded-xl"><RefreshCw data-icon="inline-start" />重置筛选</Button>
                <Button type="button" variant="outline" onClick={() => void openStatistics()} className="rounded-xl"><BarChart3 data-icon="inline-start" />统计当前结果</Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card className="overflow-hidden rounded-2xl shadow-sm">
          <CardHeader className="flex flex-col gap-4 border-b sm:flex-row sm:items-center sm:justify-between">
            <div>
              <CardTitle className="text-base">订单列表</CardTitle>
              <CardDescription>{total ? `共 ${total} 条订单，当前第 ${page} / ${totalPages} 页` : "当前筛选条件下暂无订单"}</CardDescription>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" className="rounded-xl" disabled={!someSelected || busy}>
                  <MoreHorizontal data-icon="inline-start" />批量操作{someSelected ? `（${selectedTradeNos.length}）` : ""}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-52">
                <DropdownMenuGroup>
                  {Object.entries(BATCH_LABELS).map(([key, label]) => (
                    <DropdownMenuItem key={key} disabled={!batchCounts[key]} onSelect={() => void executeBatch(key)}>
                      {label}<span className="ml-auto text-xs text-muted-foreground">{batchCounts[key] ?? 0}</span>
                    </DropdownMenuItem>
                  ))}
                </DropdownMenuGroup>
              </DropdownMenuContent>
            </DropdownMenu>
          </CardHeader>
          <CardContent className="p-0">
            {error ? (
              <div className="p-6"><Alert variant="destructive"><AlertTitle>订单列表加载失败</AlertTitle><AlertDescription>{error}</AlertDescription></Alert></div>
            ) : (
              <div className="overflow-x-auto">
              <Table className="min-w-[1280px]">
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-10"><Checkbox checked={allSelected ? true : someSelected ? "indeterminate" : false} onCheckedChange={(checked) => setSelected(checked ? new Set(rows.map((row) => stringValue(row.trade_no))) : new Set())} aria-label="选择当前页订单" /></TableHead>
                    <TableHead>订单号</TableHead><TableHead>商户</TableHead><TableHead>商品</TableHead><TableHead>金额</TableHead><TableHead>支付方式</TableHead><TableHead>时间</TableHead><TableHead>状态</TableHead><TableHead className="text-right">操作</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {loading ? (
                    <TableRow><TableCell colSpan={9} className="h-32 text-center text-muted-foreground"><Loader2 className="mr-2 inline-block animate-spin" data-icon="inline-start" />正在加载订单</TableCell></TableRow>
                  ) : rows.length ? rows.map((row) => {
                    const tradeNo = stringValue(row.trade_no)
                    return (
                      <TableRow key={tradeNo} data-state={selected.has(tradeNo) ? "selected" : undefined}>
                        <TableCell><Checkbox checked={selected.has(tradeNo)} onCheckedChange={(checked) => setSelected((current) => { const next = new Set(current); if (checked) next.add(tradeNo); else next.delete(tradeNo); return next })} aria-label={`选择订单 ${tradeNo}`} /></TableCell>
                        <TableCell className="min-w-52"><Button variant="link" className="h-auto max-w-64 justify-start px-0 font-mono text-xs whitespace-normal" onClick={() => void openDetail(tradeNo)}>{tradeNo}</Button><span className="block max-w-64 truncate text-xs text-muted-foreground">{stringValue(row.out_trade_no, "无商户订单号")}</span></TableCell>
                        <TableCell className="min-w-36"><span className="font-medium">{stringValue(row.uid, "—")}</span><span className="block max-w-44 truncate text-xs text-muted-foreground">{stringValue(row.domain, "—")}</span></TableCell>
                        <TableCell className="min-w-48 max-w-64 whitespace-normal"><span className="font-medium">{stringValue(row.name, "未命名商品")}</span><span className="block text-xs text-muted-foreground">{money(row.money)}</span></TableCell>
                        <TableCell className="min-w-32"><span className="font-medium">{money(row.realmoney ?? row.money)}</span><span className="block text-xs text-muted-foreground">分成 {money(row.getmoney)}</span></TableCell>
                        <TableCell className="min-w-36"><span className="font-medium">{displayType(row)}</span><span className="block max-w-40 truncate text-xs text-muted-foreground">{stringValue(row.plugin, "—")}</span></TableCell>
                        <TableCell className="min-w-36"><span className="block text-xs">{stringValue(row.addtime, "—")}</span><span className="block text-xs text-muted-foreground">{stringValue(row.endtime, "未完成")}</span></TableCell>
                        <TableCell>{statusBadge(row)}</TableCell>
                        <TableCell className="text-right"><DropdownMenu><DropdownMenuTrigger asChild><Button variant="outline" size="sm" className="rounded-lg"><MoreHorizontal data-icon="inline-start" />操作</Button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-48"><DropdownMenuItem onSelect={() => void openDetail(tradeNo)}><Eye data-icon="inline-start" />查看详情</DropdownMenuItem><DropdownMenuSeparator />{actionItems(row).map((item) => <DropdownMenuItem key={item.key} variant={item.destructive ? "destructive" : "default"} onSelect={() => rowAction(row, item.key)}>{item.destructive ? <Trash2 data-icon="inline-start" /> : null}{item.label}</DropdownMenuItem>)}</DropdownMenuContent></DropdownMenu></TableCell>
                      </TableRow>
                    )
                  }) : <TableRow><TableCell colSpan={9} className="h-32 text-center text-muted-foreground">暂无符合条件的订单</TableCell></TableRow>}
                </TableBody>
              </Table>
              </div>
            )}
          </CardContent>
          <CardFooter className="flex flex-col gap-3 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-muted-foreground">显示 {total ? (page - 1) * pageSize + 1 : 0}–{Math.min(page * pageSize, total)} / {total} 条</p>
            <div className="flex flex-wrap items-center gap-2">
              <Select value={String(pageSize)} onValueChange={changePageSize}><SelectTrigger className="w-28" aria-label="每页数量"><SelectValue /></SelectTrigger><SelectContent><SelectGroup>{[10, 30, 50, 100].map((size) => <SelectItem key={size} value={String(size)}>{size} 条 / 页</SelectItem>)}</SelectGroup></SelectContent></Select>
              <Button variant="outline" size="sm" className="rounded-lg" onClick={() => changePage(page - 1)} disabled={page <= 1 || loading}><ChevronLeft data-icon="inline-start" />上一页</Button>
              <span className="min-w-20 text-center text-sm text-muted-foreground">第 {page} / {totalPages} 页</span>
              <Button variant="outline" size="sm" className="rounded-lg" onClick={() => changePage(page + 1)} disabled={page >= totalPages || loading}>下一页<ChevronRight data-icon="inline-end" /></Button>
            </div>
          </CardFooter>
        </Card>
      </div>

      <Dialog open={statisticsOpen} onOpenChange={setStatisticsOpen}>
        <DialogContent className="max-w-3xl">
          <DialogHeader><DialogTitle>订单统计概况</DialogTitle><DialogDescription>统计当前筛选条件下的订单金额、数量与成功率。</DialogDescription></DialogHeader>
          {statisticsLoading ? <div className="flex min-h-32 items-center justify-center text-muted-foreground"><Loader2 className="mr-2 animate-spin" data-icon="inline-start" />正在计算统计数据</div> : statistics ? <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{[["订单总金额", money(statistics.totalMoney)], ["已支付金额", money(statistics.successMoney)], ["未支付金额", money(statistics.unpaidMoney)], ["已退款金额", money(statistics.refundMoney)], ["订单总数", stringValue(statistics.totalCount, "0")], ["成功率", `${stringValue(statistics.successRate, "0")}%`]].map(([label, value]) => <Card key={label} className="rounded-xl shadow-none"><CardHeader className="gap-1 p-4"><CardDescription>{label}</CardDescription><CardTitle className="text-xl">{value}</CardTitle></CardHeader></Card>)}</div> : <p className="py-8 text-center text-sm text-muted-foreground">暂无统计数据</p>}
        </DialogContent>
      </Dialog>

      <Dialog open={detailOpen} onOpenChange={setDetailOpen}>
        <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
          <DialogHeader><DialogTitle>订单详情</DialogTitle><DialogDescription>{detail ? stringValue(detail.trade_no) : "正在读取订单详情"}</DialogDescription></DialogHeader>
          {detailLoading ? <div className="flex min-h-40 items-center justify-center text-muted-foreground"><Loader2 className="mr-2 animate-spin" data-icon="inline-start" />正在读取订单详情</div> : detail ? <div className="flex flex-col gap-5"><Table><TableBody>{detailEntries.filter(([, value]) => value !== undefined && value !== null && value !== "").map(([label, value]) => <TableRow key={String(label)}><TableHead className="w-36 bg-muted/30">{label}</TableHead><TableCell className="whitespace-normal break-all">{String(value)}</TableCell></TableRow>)}</TableBody></Table>{subOrders.length ? <div><h3 className="mb-2 text-sm font-medium">子订单</h3><Table><TableHeader><TableRow><TableHead>子订单号</TableHead><TableHead>接口订单号</TableHead><TableHead>金额</TableHead><TableHead>状态</TableHead></TableRow></TableHeader><TableBody>{subOrders.map((item) => <TableRow key={String(item.sub_trade_no)}><TableCell className="font-mono text-xs">{String(item.sub_trade_no ?? "—")}</TableCell><TableCell className="font-mono text-xs">{String(item.api_trade_no ?? "—")}</TableCell><TableCell>{money(item.money)}</TableCell><TableCell>{statusLabel(item)}</TableCell></TableRow>)}</TableBody></Table></div> : null}<div className="flex flex-wrap gap-2"><Button variant="outline" className="rounded-lg" onClick={() => rowAction(detail, "notify")}><ShieldCheck data-icon="inline-start" />重新通知</Button><Button variant="outline" className="rounded-lg" onClick={() => rowAction(detail, "return")}><FileText data-icon="inline-start" />同步通知</Button></div></div> : <p className="py-8 text-center text-sm text-muted-foreground">订单不存在或已被删除。</p>}
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(refund)} onOpenChange={(open) => { if (!open) setRefund(null) }}>
        <DialogContent>
          <DialogHeader><DialogTitle>{refund?.api ? "API 退款" : "手动退款"}</DialogTitle><DialogDescription>{refund?.api ? "退款将直接调用支付渠道，请输入支付密码确认。" : "退款金额会从商户分成中扣除，请核对金额。"}</DialogDescription></DialogHeader>
          <FieldGroup><Field><FieldLabel htmlFor="refund-money">退款金额</FieldLabel><Input id="refund-money" inputMode="decimal" value={refund?.money ?? ""} onChange={(event) => setRefund((current) => current ? { ...current, money: event.target.value } : current)} /></Field>{refund?.api ? <Field><FieldLabel htmlFor="refund-paypwd">支付密码</FieldLabel><Input id="refund-paypwd" type="password" autoComplete="current-password" value={refund.paypwd} onChange={(event) => setRefund((current) => current ? { ...current, paypwd: event.target.value } : current)} /></Field> : null}</FieldGroup>
          <DialogFooter><Button variant="outline" onClick={() => setRefund(null)}>取消</Button><Button onClick={() => void submitRefund()} disabled={!refund?.money || (refund.api && !refund.paypwd) || busy}>确认退款</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(batchRefund)} onOpenChange={(open) => { if (!open) setBatchRefund(null) }}>
        <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
          <DialogHeader><DialogTitle>批量 API 退款</DialogTitle><DialogDescription>逐笔调用支付渠道退款。请核对金额并输入支付密码。</DialogDescription></DialogHeader>
          {batchRefund ? <FieldGroup>
            {batchRefund.rows.map((row) => {
              const tradeNo = stringValue(row.trade_no)
              return <Field key={tradeNo}><FieldLabel htmlFor={`batch-refund-${tradeNo}`}>{tradeNo}</FieldLabel><Input id={`batch-refund-${tradeNo}`} inputMode="decimal" value={batchRefund.money[tradeNo] ?? ""} onChange={(event) => setBatchRefund((current) => current ? { ...current, money: { ...current.money, [tradeNo]: event.target.value } } : current)} /></Field>
            })}
            <Field><FieldLabel htmlFor="batch-refund-paypwd">支付密码</FieldLabel><Input id="batch-refund-paypwd" type="password" autoComplete="current-password" value={batchRefund.paypwd} onChange={(event) => setBatchRefund((current) => current ? { ...current, paypwd: event.target.value } : current)} /></Field>
          </FieldGroup> : null}
          <DialogFooter><Button variant="outline" onClick={() => setBatchRefund(null)}>取消</Button><Button onClick={() => void submitBatchRefund()} disabled={!batchRefund?.paypwd || busy}>确认批量退款</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      <ConfirmDialog state={confirm} open={Boolean(confirm)} onOpenChange={(open) => { if (!open) setConfirm(null) }} busy={busy} />
    </div>
  )
}
