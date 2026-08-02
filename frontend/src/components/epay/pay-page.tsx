import {
  Delete,
  LockKeyhole,
  MessageSquarePlus,
  ShieldCheck,
} from "lucide-react"
import * as React from "react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Textarea } from "@/components/ui/textarea"

export type PayPageConfig = {
  uid?: string | number
  token?: string
  paytype?: string
  direct?: string | number
  payer?: string
  money?: string | number | null
  codename?: string
  sitename?: string
}

const keys = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0", "dot"]

export function PayPageView({ config = {} }: { config?: PayPageConfig }) {
  const [remark, setRemark] = React.useState("")
  const [remarkOpen, setRemarkOpen] = React.useState(false)
  const [draftRemark, setDraftRemark] = React.useState("")
  const fixedAmount =
    config.money !== null && config.money !== undefined && config.money !== ""

  const openRemark = () => {
    setDraftRemark(remark)
    setRemarkOpen(true)
  }
  const saveRemark = () => {
    if (draftRemark.length > 30) return
    setRemark(draftRemark)
    setRemarkOpen(false)
  }

  return (
    <div className="epay-pay-page min-h-svh bg-muted/30 px-4 py-6 text-foreground sm:px-6">
      <div className="mx-auto grid max-w-5xl gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(360px,440px)] lg:items-start">
        <Card className="rounded-3xl shadow-sm">
          <CardHeader className="flex flex-row items-center justify-between gap-4 border-b p-5 sm:p-7">
            <div className="flex items-center gap-3">
              <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                <ShieldCheck className="size-5" />
              </span>
              <div>
                <p className="text-xs text-muted-foreground">收款至</p>
                <CardTitle className="mt-1 text-xl tracking-tight">
                  {config.codename || "商户"}
                </CardTitle>
              </div>
            </div>
            <Badge variant="secondary" className="rounded-lg">
              <LockKeyhole className="text-primary" data-icon="inline-start" />
              安全支付
            </Badge>
          </CardHeader>
          <CardContent className="p-5 sm:p-7">
            <form name="payForm" action="dopay" method="post">
              <input
                type="hidden"
                name="uid"
                id="uid"
                value={String(config.uid ?? "")}
              />
              <input
                type="hidden"
                name="token"
                id="token"
                value={String(config.token ?? "")}
              />
              <input
                type="hidden"
                name="paytype"
                id="paytype"
                value={String(config.paytype ?? "")}
              />
              <input
                type="hidden"
                name="direct"
                id="direct"
                value={String(config.direct ?? "0")}
              />
              <input
                type="hidden"
                name="payer"
                id="payer"
                value={String(config.payer ?? "")}
              />
              <input type="hidden" name="trade_no" id="trade_no" value="" />
              {fixedAmount && (
                <input
                  type="hidden"
                  name="txAmount"
                  id="txAmount"
                  value={String(config.money)}
                />
              )}
              <div className="rounded-2xl bg-muted/50 p-5 text-center">
                <p className="text-sm text-muted-foreground">请输入付款金额</p>
                <div className="mt-2 flex items-center justify-center gap-1 text-4xl font-semibold tracking-tight text-primary">
                  <span>¥</span>
                  <span id="amount" aria-live="polite" aria-label="付款金额" />
                </div>
                <span
                  id="line"
                  className="mx-auto mt-2 block h-0.5 w-24 rounded-full bg-primary/40"
                />
              </div>
              <div className="mt-5 flex items-center justify-between gap-3 rounded-2xl border p-4">
                <div className="min-w-0">
                  <p className="text-xs text-muted-foreground">备注</p>
                  <p id="remark-content" className="mt-1 truncate text-sm">
                    {remark || "未添加备注"}
                  </p>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="shrink-0 rounded-xl"
                  onClick={openRemark}
                >
                  <MessageSquarePlus data-icon="inline-start" />
                  {remark ? "编辑" : "添加"}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
        <div className="flex flex-col gap-5">
          <Card className="rounded-3xl shadow-sm">
            <CardHeader className="border-b p-5">
              <CardTitle className="text-base">输入金额</CardTitle>
            </CardHeader>
            <CardContent className="p-4">
              <div
                id="keyboard"
                className="grid grid-cols-3 gap-2"
                role="group"
                aria-label="金额键盘"
              >
                {keys.map((key) => (
                  <Button
                    key={key}
                    type="button"
                    variant="outline"
                    data-value={key}
                    className="h-14 rounded-2xl text-lg font-medium transition-transform active:scale-95"
                  >
                    {key === "dot" ? "." : key}
                  </Button>
                ))}
                <Button
                  type="button"
                  id="clearBtn"
                  data-value="delete"
                  variant="outline"
                  className="none h-14 rounded-2xl text-lg"
                  aria-label="清除金额"
                >
                  <Delete className="pointer-events-none" />
                </Button>
              </div>
              <Button
                type="button"
                id="payBtn"
                className="mt-3 h-14 w-full rounded-2xl text-base"
                aria-label="确认支付"
              >
                <span className="text-primary-foreground/70">确认</span>
                支付
              </Button>
            </CardContent>
          </Card>
          <div className="flex items-center justify-center gap-2 text-xs text-muted-foreground">
            <ShieldCheck className="size-4 text-primary" />由{" "}
            {config.sitename || "Rainbow Pay"} 提供安全支付服务
          </div>
        </div>
      </div>
      <Dialog open={remarkOpen} onOpenChange={setRemarkOpen}>
        <DialogContent className="rounded-2xl sm:max-w-md">
          <DialogHeader>
            <DialogTitle>添加备注</DialogTitle>
            <DialogDescription>备注内容不能超过 30 个字。</DialogDescription>
          </DialogHeader>
          <Textarea
            value={draftRemark}
            onChange={(event) => setDraftRemark(event.target.value)}
            maxLength={30}
            placeholder="请输入备注内容"
            className="min-h-28 rounded-xl"
          />
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              className="rounded-xl"
              onClick={() => setRemarkOpen(false)}
            >
              取消
            </Button>
            <Button
              type="button"
              className="rounded-xl"
              onClick={saveRemark}
              disabled={draftRemark.length > 30}
            >
              确认
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
