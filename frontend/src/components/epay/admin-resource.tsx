import * as React from "react"
import {
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  Loader2,
  RefreshCw,
  Search,
  Trash2,
} from "lucide-react"

import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { cn } from "@/lib/utils"

type JsonObject = Record<string, unknown>

export type AdminResourceConfig = {
  resource?: string
  title?: string
  description?: string
  sitename?: string
  csrf_token?: string
  rows?: JsonObject[]
  headerActions?: readonly { label: string; href: string }[]
}

type FilterOption = readonly [string, string]
type FilterDefinition = {
  key: string
  label: string
  type?: "text" | "date" | "select"
  placeholder?: string
  options?: readonly FilterOption[]
}
type ColumnDefinition = {
  key: string
  label: string
  className?: string
}
type ResourceDefinition = {
  title: string
  description: string
  endpoint?: string
  response?: "paged" | "array" | "local"
  pageSize?: number
  filters: readonly FilterDefinition[]
  columns: readonly ColumnDefinition[]
  bulk?: { endpoint: string; valueKey: "id" | "biz_no"; options: readonly FilterOption[] }
}
type Filters = Record<string, string>
type Notice = { kind: "success" | "error"; text: string }
type Action = {
  label: string
  href?: string
  endpoint?: string
  method?: "GET" | "POST"
  values?: Record<string, string | number>
  messageKey?: string
  destructive?: boolean
}
type ConfirmState = { title: string; description: string; action: Action }

const ALL = "__all__"
const STATUS_OPTIONS: readonly FilterOption[] = [
  ["-1", "全部状态"],
  ["0", "未处理"],
  ["1", "已启用 / 成功"],
  ["2", "已退款 / 失败"],
  ["3", "已冻结 / 待处理"],
]

const RESOURCE_DEFINITIONS: Record<string, ResourceDefinition> = {
  users: {
    title: "商户列表",
    description: "查询商户账户、余额、用户组和最近登录信息。",
    endpoint: "ajax_user.php?act=userList",
    filters: [
      {
        key: "column",
        label: "搜索字段",
        type: "select",
        options: [
          ["uid", "商户 ID"],
          ["account", "登录账号"],
          ["username", "商户名称"],
          ["email", "邮箱"],
          ["phone", "手机号"],
          ["url", "网站域名"],
        ],
      },
      { key: "value", label: "搜索内容", placeholder: "输入精确值" },
      { key: "gid", label: "用户组 ID", placeholder: "留空为全部" },
      { key: "order_days", label: "无订单天数", placeholder: "例如 30" },
    ],
    columns: [
      { key: "uid", label: "商户 ID" },
      { key: "account", label: "登录账号" },
      { key: "username", label: "商户名称" },
      { key: "groupname", label: "用户组" },
      { key: "money", label: "余额" },
      { key: "status", label: "状态" },
      { key: "addtime", label: "注册时间" },
      { key: "lasttime", label: "最近登录" },
    ],
  },
  channels: {
    title: "支付通道",
    description: "管理支付方式、支付插件、费率与通道状态。",
    endpoint: "ajax_pay.php?act=channelList",
    response: "array",
    filters: [
      { key: "kw", label: "通道关键词", placeholder: "ID 或通道名称" },
      { key: "type", label: "支付方式 ID", placeholder: "留空为全部" },
      { key: "plugin", label: "插件名称", placeholder: "例如 alipay" },
      { key: "dstatus", label: "状态", type: "select", options: STATUS_OPTIONS },
    ],
    columns: [
      { key: "id", label: "ID" },
      { key: "typeshowname", label: "支付方式" },
      { key: "name", label: "通道名称" },
      { key: "plugin", label: "插件" },
      { key: "rate", label: "费率" },
      { key: "status", label: "状态" },
    ],
  },
  types: {
    title: "支付方式",
    description: "管理系统可用的支付方式与设备类型。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "调用值或显示名称" }],
    columns: [
      { key: "id", label: "ID" },
      { key: "name", label: "调用值" },
      { key: "showname", label: "显示名称" },
      { key: "device", label: "设备" },
      { key: "status", label: "状态" },
    ],
  },
  settles: {
    title: "结算管理",
    description: "查看商户结算申请、状态和收款账户。",
    endpoint: "ajax_settle.php?act=settleList",
    filters: [
      { key: "batch", label: "批次号", placeholder: "留空为全部" },
      { key: "uid", label: "商户 ID", placeholder: "留空为全部" },
      { key: "value", label: "账号或姓名", placeholder: "模糊搜索" },
      { key: "dstatus", label: "状态", type: "select", options: STATUS_OPTIONS },
    ],
    columns: [
      { key: "id", label: "ID" },
      { key: "uid", label: "商户 ID" },
      { key: "account", label: "收款账号" },
      { key: "username", label: "收款人" },
      { key: "money", label: "申请金额" },
      { key: "realmoney", label: "实付金额" },
      { key: "status", label: "状态" },
      { key: "addtime", label: "申请时间" },
      { key: "endtime", label: "完成时间" },
    ],
    bulk: { endpoint: "ajax_settle.php?act=opslist", valueKey: "id", options: [["0", "待结算"], ["1", "已完成"], ["2", "处理中"], ["3", "结算失败"], ["4", "删除记录"]] },
  },
  transfers: {
    title: "付款记录",
    description: "查看平台代付、红包和转账处理状态。",
    endpoint: "ajax_transfer.php?act=transferList",
    filters: [
      { key: "column", label: "搜索字段", type: "select", options: [["biz_no", "业务单号"], ["username", "收款人"], ["desc", "备注"]] },
      { key: "value", label: "搜索内容", placeholder: "输入关键词" },
      { key: "uid", label: "商户 ID", placeholder: "留空为全部" },
      { key: "dstatus", label: "状态", type: "select", options: STATUS_OPTIONS },
      { key: "starttime", label: "开始日期", type: "date" },
      { key: "endtime", label: "结束日期", type: "date" },
    ],
    columns: [
      { key: "biz_no", label: "业务单号" },
      { key: "uid", label: "商户 ID" },
      { key: "username", label: "收款人" },
      { key: "money", label: "金额" },
      { key: "type", label: "付款方式" },
      { key: "status", label: "状态" },
      { key: "addtime", label: "提交时间" },
      { key: "paytime", label: "完成时间" },
    ],
    bulk: { endpoint: "ajax_transfer.php?act=operation", valueKey: "biz_no", options: [["1", "改为成功"], ["2", "改为失败"], ["3", "删除记录"]] },
  },
  records: {
    title: "资金明细",
    description: "查看商户余额变动、订单关联和资金方向。",
    endpoint: "ajax_user.php?act=recordList",
    filters: [
      { key: "uid", label: "商户 ID", placeholder: "留空为全部" },
      { key: "column", label: "搜索字段", type: "select", options: [["id", "记录 ID"], ["uid", "商户 ID"], ["trade_no", "订单号"], ["action", "动作"]] },
      { key: "value", label: "搜索内容", placeholder: "输入精确值" },
      { key: "starttime", label: "开始日期", type: "date" },
      { key: "endtime", label: "结束日期", type: "date" },
    ],
    columns: [
      { key: "id", label: "ID" },
      { key: "uid", label: "商户 ID" },
      { key: "action", label: "动作" },
      { key: "money", label: "金额" },
      { key: "type", label: "类型" },
      { key: "trade_no", label: "订单号" },
      { key: "date", label: "时间" },
    ],
  },
  logs: {
    title: "登录日志",
    description: "审计管理员和商户的登录、鉴权及安全事件。",
    endpoint: "ajax_user.php?act=logList",
    filters: [
      { key: "column", label: "搜索字段", type: "select", options: [["uid", "商户 ID"], ["type", "事件类型"], ["ip", "IP 地址"], ["city", "城市"]] },
      { key: "value", label: "搜索内容", placeholder: "输入精确值" },
    ],
    columns: [
      { key: "id", label: "ID" },
      { key: "uid", label: "商户 ID" },
      { key: "type", label: "事件" },
      { key: "ip", label: "IP 地址" },
      { key: "city", label: "城市" },
      { key: "date", label: "时间" },
    ],
  },
  risks: {
    title: "风控记录",
    description: "查看风险命中记录和处理状态。",
    endpoint: "ajax_order.php?act=riskList",
    filters: [
      { key: "column", label: "搜索字段", type: "select", options: [["id", "记录 ID"], ["uid", "商户 ID"], ["url", "URL"], ["content", "内容"]] },
      { key: "value", label: "搜索内容", placeholder: "输入精确值" },
      { key: "type", label: "风控类型", placeholder: "留空为全部" },
    ],
    columns: [
      { key: "id", label: "ID" },
      { key: "uid", label: "商户 ID" },
      { key: "type", label: "类型" },
      { key: "url", label: "URL" },
      { key: "content", label: "命中内容" },
      { key: "status", label: "状态" },
      { key: "date", label: "时间" },
    ],
  },
  domains: {
    title: "授权域名",
    description: "审核商户支付域名并维护授权状态。",
    endpoint: "ajax_user.php?act=domainList",
    filters: [
      { key: "kw", label: "域名关键词", placeholder: "输入域名" },
      { key: "uid", label: "商户 ID", placeholder: "留空为全部" },
      { key: "dstatus", label: "状态", type: "select", options: STATUS_OPTIONS },
    ],
    columns: [
      { key: "id", label: "ID" },
      { key: "uid", label: "商户 ID" },
      { key: "domain", label: "域名" },
      { key: "status", label: "状态" },
      { key: "addtime", label: "申请时间" },
      { key: "endtime", label: "更新时间" },
    ],
  },
  blacklist: {
    title: "支付黑名单",
    description: "管理支付账号和 IP 黑名单。",
    endpoint: "ajax_user.php?act=blackList",
    filters: [
      { key: "kw", label: "搜索内容", placeholder: "账号或 IP" },
      { key: "type", label: "黑名单类型", type: "select", options: [["-1", "全部类型"], ["0", "支付账号"], ["1", "IP 地址"]] },
    ],
    columns: [
      { key: "id", label: "ID" },
      { key: "type", label: "类型" },
      { key: "content", label: "内容" },
      { key: "remark", label: "备注" },
      { key: "addtime", label: "添加时间" },
      { key: "endtime", label: "到期时间" },
    ],
  },
  groups: {
    title: "用户组设置",
    description: "查看用户组、费率策略和上架状态。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "用户组名称" }],
    columns: [
      { key: "gid", label: "GID" },
      { key: "name", label: "用户组名称" },
      { key: "infoText", label: "通道与费率" },
      { key: "isbuy", label: "上架状态" },
    ],
  },
  plugins: {
    title: "支付插件",
    description: "查看已安装的支付插件及支持的支付类型。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "插件名称" }],
    columns: [
      { key: "name", label: "插件标识" },
      { key: "showname", label: "插件名称" },
      { key: "types", label: "支持方式" },
      { key: "transtypes", label: "转账能力" },
    ],
  },
  batches: {
    title: "批量结算",
    description: "查看已生成结算批次并进入批次处理。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "批次号" }],
    columns: [
      { key: "batch", label: "批次号" },
      { key: "allmoney", label: "总金额" },
      { key: "count", label: "记录数" },
      { key: "time", label: "生成时间" },
      { key: "status", label: "状态" },
    ],
  },
  announcements: {
    title: "网站公告",
    description: "发布、排序和控制首页公告显示状态。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "公告内容" }],
    columns: [
      { key: "id", label: "ID" },
      { key: "content", label: "公告内容" },
      { key: "sort", label: "排序" },
      { key: "status", label: "状态" },
      { key: "addtime", label: "发布时间" },
    ],
  },
  invitecodes: {
    title: "邀请码管理",
    description: "生成、查询和清理注册邀请码。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "邀请码" }],
    columns: [
      { key: "code", label: "邀请码" },
      { key: "status", label: "状态" },
      { key: "addtime", label: "添加时间" },
      { key: "usetime", label: "使用时间" },
      { key: "uid", label: "使用者" },
    ],
  },
  "group-purchase": {
    title: "用户组购买设置",
    description: "控制用户组上架状态、售价、有效期和可见范围。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "用户组名称" }],
    columns: [
      { key: "gid", label: "GID" }, { key: "name", label: "用户组名称" }, { key: "price", label: "售价" },
      { key: "expire", label: "有效期（月）" }, { key: "sort", label: "排序" }, { key: "visible", label: "可见范围" }, { key: "isbuy", label: "上架状态" },
    ],
  },
  "ps-receivers": {
    title: "分账规则",
    description: "配置支付通道的分账接收方和比例。",
    endpoint: "ajax_profitsharing.php?act=receiverList",
    filters: [{ key: "column", label: "搜索字段", type: "select", options: [["channel", "通道 ID"], ["uid", "商户 ID"], ["info", "接收方"], ["id", "规则 ID"]] }, { key: "value", label: "搜索内容", placeholder: "输入搜索内容" }],
    columns: [{ key: "id", label: "ID" }, { key: "channelname", label: "支付通道" }, { key: "uid", label: "商户 ID" }, { key: "info", label: "接收方" }, { key: "minmoney", label: "最低金额" }, { key: "status", label: "状态" }, { key: "addtime", label: "创建时间" }],
  },
  "ps-orders": {
    title: "分账记录",
    description: "查询分账订单、状态和分账金额。",
    endpoint: "ajax_profitsharing.php?act=orderList",
    filters: [{ key: "column", label: "搜索字段", type: "select", options: [["trade_no", "系统订单号"], ["api_trade_no", "接口订单号"], ["money", "分账金额"]] }, { key: "value", label: "搜索内容", placeholder: "输入搜索内容" }, { key: "rid", label: "分账规则 ID" }, { key: "dstatus", label: "状态", type: "select", options: STATUS_OPTIONS }, { key: "starttime", label: "开始日期", type: "date" }, { key: "endtime", label: "结束日期", type: "date" }],
    columns: [{ key: "trade_no", label: "系统订单号" }, { key: "rid", label: "分账规则" }, { key: "channelid", label: "通道 ID" }, { key: "ordermoney", label: "订单金额" }, { key: "money", label: "分账金额" }, { key: "addtime", label: "时间" }, { key: "status", label: "状态" }],
  },
  weixin: {
    title: "公众号 / 小程序",
    description: "管理用于 OAuth、JSAPI 与小程序支付的微信应用。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "名称或 APPID" }],
    columns: [{ key: "id", label: "ID" }, { key: "typeText", label: "类别" }, { key: "name", label: "名称" }, { key: "appid", label: "APPID" }],
  },
  wework: {
    title: "企业微信账号",
    description: "管理企业微信客服支付账号和启用状态。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "名称或企业 ID" }],
    columns: [{ key: "id", label: "ID" }, { key: "name", label: "名称" }, { key: "appid", label: "企业 ID" }, { key: "kfnum", label: "客服账号数" }, { key: "status", label: "状态" }],
  },
  rolls: {
    title: "支付通道轮询",
    description: "管理通道轮询组和轮询策略。",
    response: "local",
    filters: [{ key: "value", label: "搜索内容", placeholder: "轮询组名称" }],
    columns: [{ key: "id", label: "ID" }, { key: "name", label: "显示名称" }, { key: "typeText", label: "支付方式" }, { key: "kindText", label: "轮询方式" }, { key: "info", label: "轮询规则" }, { key: "status", label: "状态" }],
  },
}

const DEFAULT_RESOURCE: ResourceDefinition = {
  title: "后台列表",
  description: "使用统一的 shadcn 管理界面查看数据。",
  response: "local",
  filters: [{ key: "value", label: "搜索内容", placeholder: "输入关键词" }],
  columns: [],
}

function stringValue(value: unknown, fallback = "") {
  if (value === null || value === undefined) return fallback
  return String(value)
}

function numberValue(value: unknown) {
  const number = Number(value)
  return Number.isFinite(number) ? number : 0
}

function money(value: unknown) {
  const number = Number(value)
  return Number.isFinite(number) ? `¥ ${number.toFixed(2)}` : "—"
}

function readFilters(definition: ResourceDefinition) {
  const defaults: Filters = Object.fromEntries(
    definition.filters.map((filter) => [filter.key, filter.type === "select" ? filter.options?.[0]?.[0] ?? "" : ""])
  )
  if (typeof window === "undefined") return defaults
  const params = new URLSearchParams(window.location.search)
  definition.filters.forEach((filter) => {
    const value = params.get(filter.key)
    if (value !== null) defaults[filter.key] = value
  })
  return defaults
}

function statusText(resource: string, value: unknown) {
  const status = numberValue(value)
  if (resource === "invitecodes") return status === 1 ? "已使用" : "未使用"
  if (resource === "domains") return status === 1 ? "正常" : status === 2 ? "已拒绝" : "待审核"
  if (resource === "blacklist") return status === 1 ? "有效" : "已过期"
  if (resource === "channels" || resource === "types") return status === 1 ? "已启用" : "已停用"
  if (resource === "settles") return ["待处理", "已完成", "处理中", "已拒绝"][status] ?? `状态 ${status}`
  if (resource === "transfers") return ["待领取", "成功", "失败", "处理中", "红包"][status] ?? `状态 ${status}`
  return status === 1 ? "正常" : status === 0 ? "未处理" : `状态 ${status}`
}

function cellValue(resource: string, key: string, value: unknown) {
  if (value === null || value === undefined || value === "") return "—"
  if (["money", "realmoney", "getmoney", "profitmoney", "allmoney"].includes(key)) return money(value)
  if (key === "status" || key === "isbuy") return statusText(resource, value)
  return String(value)
}

function actionList(resource: string, row: JsonObject): Action[] {
  const id = stringValue(row.id || row.gid)
  const uid = stringValue(row.uid)
  const tradeNo = stringValue(row.trade_no)
  const bizNo = stringValue(row.biz_no)
  const status = numberValue(row.status)
  if (resource === "users") {
    return [
      { label: "编辑", href: `./uset.php?my=edit&uid=${encodeURIComponent(uid)}` },
      { label: "模拟登录", href: `./sso.php?uid=${encodeURIComponent(uid)}` },
      { label: "订单", href: `./order.php?uid=${encodeURIComponent(uid)}` },
      { label: "明细", href: `./record.php?uid=${encodeURIComponent(uid)}` },
      { label: "结算", href: `./slist.php?uid=${encodeURIComponent(uid)}` },
      { label: status === 1 ? "封禁" : "启用", endpoint: "ajax_user.php?act=setUser", method: "POST", values: { uid, type: "user", status: status === 1 ? 0 : 1 } },
      { label: Number(row.pay) === 1 ? "关闭收款" : "开启收款", endpoint: "ajax_user.php?act=setUser", method: "POST", values: { uid, type: "pay", status: Number(row.pay) === 1 ? 0 : 1 } },
      { label: Number(row.settle) === 1 ? "关闭结算" : "开启结算", endpoint: "ajax_user.php?act=setUser", method: "POST", values: { uid, type: "settle", status: Number(row.settle) === 1 ? 0 : 1 } },
      { label: "删除", endpoint: `ajax_user.php?act=delUser&uid=${encodeURIComponent(uid)}`, method: "GET", destructive: true },
    ]
  }
  if (resource === "channels") {
    return [
      { label: "编辑", href: `./pay_channel.php?id=${encodeURIComponent(id)}` },
      { label: "复制", href: `./pay_channel.php?my=copy&id=${encodeURIComponent(id)}` },
      { label: "配置密钥", href: `./pay_channel.php?my=config&id=${encodeURIComponent(id)}` },
      { label: "测试支付", href: `./pay_channel.php?my=test&id=${encodeURIComponent(id)}` },
      { label: "订单", href: `./order.php?channel=${encodeURIComponent(id)}` },
      { label: "今日笔数", endpoint: `ajax_pay.php?act=getChannelMoney&type=2&channel=${encodeURIComponent(id)}`, method: "GET", messageKey: "money" },
      { label: "昨日笔数", endpoint: `ajax_pay.php?act=getChannelMoney&type=3&channel=${encodeURIComponent(id)}`, method: "GET", messageKey: "money" },
      { label: "今日收款", endpoint: `ajax_pay.php?act=getChannelMoney&type=0&channel=${encodeURIComponent(id)}`, method: "GET", messageKey: "money" },
      { label: "昨日收款", endpoint: `ajax_pay.php?act=getChannelMoney&type=1&channel=${encodeURIComponent(id)}`, method: "GET", messageKey: "money" },
      { label: "成功率", endpoint: `ajax_pay.php?act=getSuccessRate&channel=${encodeURIComponent(id)}`, method: "GET", messageKey: "data" },
      { label: "插件详情", href: `./pay_plugin.php?plugin=${encodeURIComponent(stringValue(row.plugin))}` },
      { label: status === 1 ? "停用" : "启用", endpoint: `ajax_pay.php?act=setChannel&id=${encodeURIComponent(id)}&status=${status === 1 ? 0 : 1}`, method: "GET" },
      { label: "删除", endpoint: `ajax_pay.php?act=delChannel&id=${encodeURIComponent(id)}`, method: "GET", destructive: true },
    ]
  }
  if (resource === "types") {
    return [
      { label: "编辑", href: `./pay_type.php?id=${encodeURIComponent(id)}` },
      { label: "订单", href: `./order.php?type=${encodeURIComponent(id)}` },
      { label: status === 1 ? "停用" : "启用", endpoint: `ajax_pay.php?act=setPayType&id=${encodeURIComponent(id)}&status=${status === 1 ? 0 : 1}`, method: "GET" },
      { label: "删除", endpoint: `ajax_pay.php?act=delPayType&id=${encodeURIComponent(id)}`, method: "GET", destructive: true },
    ]
  }
  if (resource === "settles") {
    return [
      { label: "商户", href: `./ulist.php?my=search&column=uid&value=${encodeURIComponent(uid)}` },
      { label: "标记完成", endpoint: `ajax_settle.php?act=setSettleStatus&id=${encodeURIComponent(id)}&status=1`, method: "GET" },
      { label: "删除", endpoint: `ajax_settle.php?act=setSettleStatus&id=${encodeURIComponent(id)}&status=4`, method: "GET", destructive: true },
    ]
  }
  if (resource === "transfers") {
    return [
      { label: "复制", href: `./transfer_add.php?copy=${encodeURIComponent(bizNo)}` },
      { label: "查询状态", endpoint: `ajax_transfer.php?act=transfer_query&biz_no=${encodeURIComponent(bizNo)}`, method: "GET" },
      { label: "删除", endpoint: "ajax_transfer.php?act=delTransfer", method: "POST", values: { biz_no: bizNo }, destructive: true },
    ]
  }
  if (resource === "domains") {
    return [
      { label: status === 1 ? "拒绝" : "通过", endpoint: "ajax_user.php?act=setDomainStatus", method: "POST", values: { id, status: status === 1 ? 2 : 1 } },
      { label: "删除", endpoint: "ajax_user.php?act=delDomain", method: "POST", values: { id }, destructive: true },
    ]
  }
  if (resource === "blacklist") {
    return [{ label: "删除", endpoint: "ajax_user.php?act=delBlack", method: "POST", values: { id }, destructive: true }]
  }
  if (resource === "groups") return [{ label: "编辑", href: `./gedit.php?act=edit&gid=${encodeURIComponent(id)}` }, { label: "用户", href: `./ulist.php?gid=${encodeURIComponent(id)}` }, { label: "删除", endpoint: `ajax_user.php?act=delGroup&gid=${encodeURIComponent(id)}`, method: "GET", destructive: true }]
  if (resource === "plugins") return [{ label: "查看通道", href: `./pay_channel.php?plugin=${encodeURIComponent(stringValue(row.name))}` }]
  if (resource === "batches") return [{ label: "结算列表", href: `./slist.php?batch=${encodeURIComponent(stringValue(row.batch))}` }, { label: "批量转账", href: `./settle_batch.php?batch=${encodeURIComponent(stringValue(row.batch))}` }]
  if (resource === "announcements") return [
    { label: "编辑", href: `./gonggao.php?my=edit&id=${encodeURIComponent(id)}` },
    { label: status === 1 ? "隐藏" : "显示", endpoint: `ajax.php?act=setGonggao&id=${encodeURIComponent(id)}&status=${status === 1 ? 0 : 1}`, method: "GET" },
    { label: "删除", endpoint: `ajax.php?act=delGonggao&id=${encodeURIComponent(id)}`, method: "GET", destructive: true },
  ]
  if (resource === "invitecodes") return [{ label: "删除", endpoint: `ajax.php?act=delInvite&id=${encodeURIComponent(id)}`, method: "POST", destructive: true }]
  if (resource === "group-purchase") return [{ label: status === 1 ? "下架" : "上架", endpoint: "ajax_user.php?act=saveGroup", method: "POST", values: { action: "changebuy", gid: id, status: status === 1 ? 0 : 1 } }]
  if (resource === "ps-receivers") return [{ label: status === 1 ? "停用" : "启用", endpoint: "ajax_profitsharing.php?act=set_receiver", method: "POST", values: { id, status: status === 1 ? 0 : 1 } }, { label: "删除", endpoint: "ajax_profitsharing.php?act=del_receiver", method: "POST", values: { id }, destructive: true }, { label: "记录", href: `./ps_order.php?rid=${encodeURIComponent(id)}` }]
  if (resource === "ps-orders") return [{ label: "订单", href: `./order.php?column=trade_no&value=${encodeURIComponent(tradeNo)}` }, { label: "分账规则", href: `./ps_receiver.php?column=id&value=${encodeURIComponent(stringValue(row.rid))}` }]
  if (resource === "weixin") return [{ label: "编辑", href: `./pay_weixin.php?my=edit&id=${encodeURIComponent(id)}` }, { label: "测试", endpoint: `ajax_pay.php?act=testweixin&id=${encodeURIComponent(id)}`, method: "POST" }, { label: "删除", endpoint: `ajax_pay.php?act=delWeixin&id=${encodeURIComponent(id)}`, method: "GET", destructive: true }]
  if (resource === "wework") return [{ label: "编辑", href: `./pay_wework.php?my=edit&id=${encodeURIComponent(id)}` }, { label: "刷新客服", endpoint: "ajax_pay.php?act=refreshWework", method: "POST", values: { id } }, { label: "测试连接", endpoint: "ajax_pay.php?act=testWework", method: "POST", values: { id } }, { label: status === 1 ? "停用" : "启用", endpoint: `ajax_pay.php?act=setWework&id=${encodeURIComponent(id)}&status=${status === 1 ? 0 : 1}`, method: "GET" }, { label: "删除", endpoint: `ajax_pay.php?act=delWework&id=${encodeURIComponent(id)}`, method: "GET", destructive: true }]
  if (resource === "rolls") return [{ label: "配置通道", href: `./pay_roll.php?my=config&id=${encodeURIComponent(id)}` }, { label: "编辑", href: `./pay_roll.php?my=edit&id=${encodeURIComponent(id)}` }, { label: status === 1 ? "停用" : "启用", endpoint: `ajax_pay.php?act=setRoll&id=${encodeURIComponent(id)}&status=${status === 1 ? 0 : 1}`, method: "GET" }, { label: "删除", endpoint: `ajax_pay.php?act=delRoll&id=${encodeURIComponent(id)}`, method: "GET", destructive: true }]
  if (resource === "records" && tradeNo) return [{ label: "订单", href: `./order.php?trade_no=${encodeURIComponent(tradeNo)}` }]
  return []
}

async function decodeJson(response: Response) {
  const data = (await response.json().catch(() => ({}))) as JsonObject
  if (!response.ok) throw new Error(stringValue(data.msg, "请求失败"))
  if (data.code !== undefined && Number(data.code) !== 0 && Number(data.code) !== 200) throw new Error(stringValue(data.msg, "操作失败"))
  return data
}

function localMatches(rows: JsonObject[], filters: Filters) {
  const query = Object.values(filters).filter((value) => value && value !== ALL).join(" ").toLowerCase()
  if (!query) return rows
  return rows.filter((row) => Object.values(row).some((value) => stringValue(value).toLowerCase().includes(query)))
}

export function AdminResourceView({ config = {} }: { config?: AdminResourceConfig }) {
  const resource = stringValue(config.resource, "users")
  const definition = RESOURCE_DEFINITIONS[resource] ?? DEFAULT_RESOURCE
  const initialFilters = React.useMemo(() => readFilters(definition), [definition])
  const [filters, setFilters] = React.useState<Filters>(initialFilters)
  const [applied, setApplied] = React.useState<Filters>(initialFilters)
  const [rows, setRows] = React.useState<JsonObject[]>(Array.isArray(config.rows) ? config.rows : [])
  const [total, setTotal] = React.useState(Array.isArray(config.rows) ? config.rows.length : 0)
  const [page, setPage] = React.useState(1)
  const [pageSize, setPageSize] = React.useState(definition.pageSize ?? 30)
  const [loading, setLoading] = React.useState(Boolean(definition.endpoint))
  const [error, setError] = React.useState("")
  const [notice, setNotice] = React.useState<Notice | null>(null)
  const [confirm, setConfirm] = React.useState<ConfirmState | null>(null)
  const [selected, setSelected] = React.useState<string[]>([])
  const [bulkValue, setBulkValue] = React.useState("")
  const csrfToken = stringValue(config.csrf_token)
  const totalPages = Math.max(1, Math.ceil(total / pageSize))

  const syncUrl = React.useCallback((next: Filters, nextPage: number, nextSize: number) => {
    if (typeof window === "undefined") return
    const url = new URL(window.location.href)
    Object.entries(next).forEach(([key, value]) => value && value !== ALL ? url.searchParams.set(key, value) : url.searchParams.delete(key))
    url.searchParams.set("pageNumber", String(nextPage))
    url.searchParams.set("pageSize", String(nextSize))
    window.history.replaceState({}, "", url)
  }, [])

  const load = React.useCallback(async () => {
    setError("")
    if (!definition.endpoint) {
      const local = localMatches(Array.isArray(config.rows) ? config.rows : [], applied)
      setRows(local)
      setTotal(local.length)
      setLoading(false)
      return
    }
    setLoading(true)
    try {
      const body = new URLSearchParams()
      body.set("csrf_token", csrfToken)
      Object.entries(applied).forEach(([key, value]) => {
        if (value && value !== ALL) body.set(key, value)
      })
      body.set("offset", String((page - 1) * pageSize))
      body.set("limit", String(pageSize))
      const response = await fetch(definition.endpoint, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      const data = await decodeJson(response)
      const nextRows = Array.isArray(data) ? data as JsonObject[] : Array.isArray(data.rows) ? data.rows as JsonObject[] : []
      setRows(nextRows)
      setTotal(Array.isArray(data) ? nextRows.length : Number(data.total) || 0)
      setSelected([])
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "数据加载失败")
    } finally {
      setLoading(false)
    }
  }, [applied, config.rows, csrfToken, definition.endpoint, page, pageSize])

  React.useEffect(() => {
    const timer = window.setTimeout(() => void load(), 0)
    return () => window.clearTimeout(timer)
  }, [load])

  const visibleRows = definition.response === "array" || !definition.endpoint ? rows.slice((page - 1) * pageSize, page * pageSize) : rows

  const announce = React.useCallback((kind: Notice["kind"], text: string) => {
    setNotice({ kind, text })
    window.setTimeout(() => setNotice(null), 4500)
  }, [])

  const execute = React.useCallback(async (action: Action) => {
    if (!action.endpoint) return
    const body = new URLSearchParams()
    body.set("csrf_token", csrfToken)
    Object.entries(action.values ?? {}).forEach(([key, value]) => body.set(key, String(value)))
    const response = await fetch(action.endpoint, { method: action.method ?? "GET", credentials: "same-origin", headers: action.method === "POST" ? { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" } : undefined, body: action.method === "POST" ? body : undefined })
    const data = await decodeJson(response)
    const result = action.messageKey ? stringValue(data[action.messageKey]) : ""
    announce("success", result ? `${action.label}：${result}` : `${action.label}成功`)
    await load()
  }, [announce, csrfToken, load])

  const handleAction = (action: Action) => {
    if (action.href) return
    if (action.destructive) {
      setConfirm({ title: `确认${action.label}？`, description: "该操作会立即写入后台数据，请确认后继续。", action })
      return
    }
    void execute(action).catch((requestError) => announce("error", requestError instanceof Error ? requestError.message : "操作失败"))
  }

  const applyBulk = async () => {
    if (!definition.bulk || !bulkValue || !selected.length) return
    setLoading(true); setError("")
    try {
      const body = new URLSearchParams({ csrf_token: csrfToken, status: bulkValue })
      selected.forEach((value) => body.append("checkbox[]", value))
      const response = await fetch(definition.bulk.endpoint, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" }, body })
      await decodeJson(response)
      announce("success", "批量操作成功")
      await load()
    } catch (requestError) {
      announce("error", requestError instanceof Error ? requestError.message : "批量操作失败")
    } finally { setLoading(false) }
  }

  const rowSelectionKey = (row: JsonObject) => stringValue(definition.bulk ? row[definition.bulk.valueKey] : row.id)
  const toggleSelected = (key: string, checked: boolean) => setSelected((current) => checked ? Array.from(new Set([...current, key])) : current.filter((value) => value !== key))

  const submitFilters = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setPage(1)
    setApplied({ ...filters })
    syncUrl(filters, 1, pageSize)
  }

  const clearFilters = () => {
    setFilters(initialFilters)
    setApplied(initialFilters)
    setPage(1)
    syncUrl(initialFilters, 1, pageSize)
  }

  const title = stringValue(config.title, definition.title)
  const description = stringValue(config.description, definition.description)
  const sitename = stringValue(config.sitename, "Rainbow Pay")

  return (
    <div className="min-w-0 w-full">
      <div className="mx-auto flex w-full max-w-[1440px] flex-col gap-6">
        <header className="flex flex-wrap items-start justify-between gap-4">
          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">{sitename}</p>
            <h1 className="mt-1 text-2xl font-semibold tracking-tight">{title}</h1>
            <p className="mt-1 max-w-3xl text-sm text-muted-foreground">{description}</p>
          </div>
          <div className="flex flex-wrap gap-2">{config.headerActions?.map((action) => <Button key={action.href} asChild variant="outline" className="rounded-xl"><a href={action.href}>{action.label}</a></Button>)}<Button variant="outline" className="rounded-xl" onClick={() => void load()} disabled={loading}>
            {loading ? <Loader2 data-icon="inline-start" className="animate-spin" /> : <RefreshCw data-icon="inline-start" />}
            刷新
          </Button></div>
        </header>

        {notice ? <Alert variant={notice.kind === "error" ? "destructive" : "default"}><AlertTitle>{notice.kind === "error" ? "操作失败" : "操作成功"}</AlertTitle><AlertDescription>{notice.text}</AlertDescription></Alert> : null}

        <Card className="rounded-2xl shadow-sm">
          <CardHeader className="border-b"><CardTitle className="text-base">筛选条件</CardTitle><CardDescription>使用组合条件查询当前后台数据。</CardDescription></CardHeader>
          <CardContent className="pt-6">
            <form onSubmit={submitFilters} className="flex flex-col gap-5">
              <FieldGroup className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {definition.filters.map((filter) => (
                  <Field key={filter.key} className={filter.type === "text" && filter.key === "value" ? "sm:col-span-2" : undefined}>
                    <FieldLabel htmlFor={`resource-${filter.key}`}>{filter.label}</FieldLabel>
                    {filter.type === "select" ? <Select value={filters[filter.key] || ALL} onValueChange={(value) => setFilters((current) => ({ ...current, [filter.key]: value === ALL ? "" : value }))}><SelectTrigger id={`resource-${filter.key}`} className="w-full"><SelectValue placeholder={filter.label} /></SelectTrigger><SelectContent><SelectGroup>{filter.options?.map(([value, label]) => <SelectItem key={value} value={value === "" ? ALL : value}>{label}</SelectItem>)}</SelectGroup></SelectContent></Select> : <Input id={`resource-${filter.key}`} type={filter.type === "date" ? "date" : "text"} inputMode={filter.key === "uid" || filter.key === "gid" ? "numeric" : undefined} value={filters[filter.key] ?? ""} onChange={(event) => setFilters((current) => ({ ...current, [filter.key]: event.target.value }))} placeholder={filter.placeholder} />}
                  </Field>
                ))}
              </FieldGroup>
              <div className="flex flex-wrap gap-2"><Button type="submit" className="rounded-xl"><Search data-icon="inline-start" />查询</Button><Button type="button" variant="outline" className="rounded-xl" onClick={clearFilters}><RefreshCw data-icon="inline-start" />重置</Button></div>
            </form>
          </CardContent>
        </Card>

        <Card className="overflow-hidden rounded-2xl shadow-sm">
          <CardHeader className="border-b"><CardTitle className="text-base">{title}</CardTitle><CardDescription>{total ? `共 ${total} 条记录，当前第 ${page} / ${totalPages} 页` : "当前筛选条件下暂无记录"}</CardDescription>{definition.bulk ? <div className="flex flex-wrap items-center gap-2 pt-2"><Select value={bulkValue || "__empty"} onValueChange={(value) => setBulkValue(value === "__empty" ? "" : value)}><SelectTrigger className="w-40" aria-label="批量操作"><SelectValue placeholder="选择批量操作" /></SelectTrigger><SelectContent><SelectGroup>{definition.bulk.options.map(([value, label]) => <SelectItem key={value} value={value}>{label}</SelectItem>)}</SelectGroup></SelectContent></Select><Button type="button" variant="outline" className="rounded-xl" disabled={!bulkValue || !selected.length || loading} onClick={() => void applyBulk()}>应用到 {selected.length} 条</Button>{selected.length ? <Button type="button" variant="ghost" className="rounded-xl" onClick={() => setSelected([])}>清除选择</Button> : null}</div> : null}</CardHeader>
          <CardContent className="p-0">
            {error ? <div className="p-6"><Alert variant="destructive"><AlertTitle>加载失败</AlertTitle><AlertDescription>{error}</AlertDescription></Alert></div> : <div className="overflow-x-auto"><Table className="min-w-[960px]"><TableHeader><TableRow>{definition.bulk ? <TableHead className="w-10"><Checkbox aria-label="选择当前页" checked={visibleRows.length > 0 && visibleRows.every((row) => selected.includes(rowSelectionKey(row)))} onCheckedChange={(checked) => { const keys = visibleRows.map(rowSelectionKey); setSelected((current) => checked === true ? Array.from(new Set([...current, ...keys])) : current.filter((key) => !keys.includes(key))) }} /></TableHead> : null}{definition.columns.map((column) => <TableHead key={column.key} className={column.className}>{column.label}</TableHead>)}<TableHead className="text-right">操作</TableHead></TableRow></TableHeader><TableBody>{loading ? <TableRow><TableCell colSpan={definition.columns.length + (definition.bulk ? 2 : 1)} className="h-32 text-center text-muted-foreground"><Loader2 data-icon="inline-start" className="animate-spin" />正在加载</TableCell></TableRow> : visibleRows.length ? visibleRows.map((row, index) => { const actions = actionList(resource, row); const selectionKey = rowSelectionKey(row); return <TableRow key={stringValue(row.id || row.uid || row.trade_no || row.biz_no, String(index))}>{definition.bulk ? <TableCell><Checkbox aria-label={`选择 ${selectionKey}`} checked={selected.includes(selectionKey)} onCheckedChange={(checked) => toggleSelected(selectionKey, checked === true)} /></TableCell> : null}{definition.columns.map((column) => { const value = cellValue(resource, column.key, row[column.key]); return <TableCell key={column.key} className={cn("max-w-64 whitespace-normal break-words", column.className)}>{column.key === "status" || column.key === "isbuy" ? <Badge variant={numberValue(row[column.key]) === 1 ? "secondary" : "outline"} className="rounded-md font-normal">{value}</Badge> : ["uid", "id"].includes(column.key) ? <span className="font-mono text-xs">{value}</span> : value}</TableCell> })}<TableCell className="text-right"><div className="flex flex-wrap justify-end gap-2">{actions.length ? actions.map((action) => action.href ? <Button key={action.label} asChild variant="outline" size="sm" className="rounded-lg"><a href={action.href}><ExternalLink data-icon="inline-start" />{action.label}</a></Button> : <Button key={action.label} variant={action.destructive ? "destructive" : "outline"} size="sm" className="rounded-lg" onClick={() => handleAction(action)}>{action.destructive ? <Trash2 data-icon="inline-start" /> : null}{action.label}</Button>) : <span className="text-xs text-muted-foreground">—</span>}</div></TableCell></TableRow> }) : <TableRow><TableCell colSpan={definition.columns.length + (definition.bulk ? 2 : 1)} className="h-32 text-center text-muted-foreground">暂无符合条件的记录</TableCell></TableRow>}</TableBody></Table></div>}
          </CardContent>
          <CardFooter className="flex flex-col gap-3 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between"><p className="text-sm text-muted-foreground">显示 {total ? (page - 1) * pageSize + 1 : 0}–{Math.min(page * pageSize, total)} / {total} 条</p><div className="flex flex-wrap items-center gap-2"><Select value={String(pageSize)} onValueChange={(value) => { const next = Number(value); setPageSize(next); setPage(1); syncUrl(applied, 1, next) }}><SelectTrigger className="w-28" aria-label="每页数量"><SelectValue /></SelectTrigger><SelectContent><SelectGroup>{[15, 30, 50, 100].map((size) => <SelectItem key={size} value={String(size)}>{size} 条 / 页</SelectItem>)}</SelectGroup></SelectContent></Select><Button variant="outline" size="sm" className="rounded-lg" onClick={() => { const next = Math.max(1, page - 1); setPage(next); syncUrl(applied, next, pageSize) }} disabled={page <= 1 || loading}><ChevronLeft data-icon="inline-start" />上一页</Button><span className="min-w-20 text-center text-sm text-muted-foreground">第 {page} / {totalPages} 页</span><Button variant="outline" size="sm" className="rounded-lg" onClick={() => { const next = Math.min(totalPages, page + 1); setPage(next); syncUrl(applied, next, pageSize) }} disabled={page >= totalPages || loading}>下一页<ChevronRight data-icon="inline-end" /></Button></div></CardFooter>
        </Card>
      </div>

      <Dialog open={Boolean(confirm)} onOpenChange={(open) => { if (!open) setConfirm(null) }}><DialogContent><DialogHeader><DialogTitle>{confirm?.title}</DialogTitle><DialogDescription>{confirm?.description}</DialogDescription></DialogHeader><DialogFooter><Button variant="outline" onClick={() => setConfirm(null)}>取消</Button><Button variant="destructive" onClick={() => { if (!confirm) return; const action = confirm.action; setConfirm(null); void execute(action).catch((requestError) => announce("error", requestError instanceof Error ? requestError.message : "操作失败")) }}>确认操作</Button></DialogFooter></DialogContent></Dialog>
    </div>
  )
}

export { RESOURCE_DEFINITIONS }
