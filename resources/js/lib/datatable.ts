// resources/js/lib/datatable.ts

import type { Ref } from 'vue'

type TableRef = Ref<{ prependRow: (row: any) => void } | null>

type ToastLike = {
    success?: (msg: string) => void
    error?: (msg: string) => void
}

export function extractRow<T extends Record<string, any>>(json: any): T & {
    created_at: string
    updated_at: string
} {
    const r = json?.data ?? json ?? {}
    return {
        created_at: r.created_at ?? new Date().toISOString(),
        updated_at: r.updated_at ?? new Date().toISOString(),
        ...(r as T),
    }
}

export function makeCreateHandlers(opts: {
    tableRef: TableRef
    toast?: ToastLike
    onReset?: () => void          // clear form / close modal, etc
    successMessage?: string       // optional custom toast
}) {
    const { tableRef, toast, onReset, successMessage } = opts

    function handleSuccess(json: any) {
        const row = extractRow(json)
        if (row?.id != null) {
            tableRef.value?.prependRow(row)
            if (toast?.success) toast.success(successMessage ?? 'Saved')
            if (onReset) onReset()
        } else {
            if (toast?.error) toast.error('Saved, but response missing id')
        }
    }

    function handleError(err: any) {
        // keep your own error extraction if you want; this is intentionally simple
        console.error('Create failed', err)
        if (toast?.error) toast.error('Save failed')
    }

    return { handleSuccess, handleError }
}
