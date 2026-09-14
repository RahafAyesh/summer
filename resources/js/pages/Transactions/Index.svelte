<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            {
                title: 'حركاتي',
                href: '/transactions',
            },
        ],
    };
</script>

<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogHeader,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import {
        AlertDialog,
        AlertDialogAction,
        AlertDialogCancel,
        AlertDialogContent,
        AlertDialogDescription,
        AlertDialogFooter,
        AlertDialogHeader,
        AlertDialogTitle,
    } from '@/components/ui/alert-dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import { Textarea } from '@/components/ui/textarea';
    import Plus from '@lucide/svelte/icons/plus';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash2 from '@lucide/svelte/icons/trash-2';

    let {
        transactions,
        categories,
        flash,
    }: {
        transactions: {
            data: Array<{
                id: number;
                type: string;
                amount: string;
                category: string;
                source: string | null;
                description: string | null;
                notes: string | null;
                date: string;
            }>;
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
        };
        categories: {
            expenses: Array<{ value: string; label: string; color: string }>;
            incomes: Array<{ value: string; label: string; color: string }>;
        };
        flash: {
            success: string | null;
            error: string | null;
        };
    } = $props();

    let activeTab = $state<'all' | 'income' | 'expense'>('all');
    let dialogOpen = $state(false);
    let editDialogOpen = $state(false);
    let deleteDialogOpen = $state(false);
    let editingTransaction = $state<any>(null);
    let deletingTransaction = $state<any>(null);

    const createForm = useForm({
        type: 'expense' as 'income' | 'expense',
        amount: '',
        category: '',
        description: '',
        date: new Date().toISOString().split('T')[0],
    });

    const editForm = useForm({
        type: 'expense' as 'income' | 'expense',
        amount: '',
        category: '',
        description: '',
        date: new Date().toISOString().split('T')[0],
    });

    const allCategories = $derived([...categories.expenses, ...categories.incomes]);
    const categoryLabelMap = $derived(Object.fromEntries(allCategories.map((c) => [c.value, c.label])));

    const currentCategories = $derived(
        createForm.type === 'income' ? categories.incomes : categories.expenses
    );

    const editCurrentCategories = $derived(
        editForm.type === 'income' ? categories.incomes : categories.expenses
    );

    function formatDate(dateStr: string): string {
        const date = new Date(dateStr);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (date.toDateString() === today.toDateString()) return 'اليوم';
        if (date.toDateString() === yesterday.toDateString()) return 'أمس';
        
        return date.toLocaleDateString('ar-SA', {
            month: 'short',
            day: 'numeric',
        });
    }

    function switchTab(tab: 'all' | 'income' | 'expense') {
        activeTab = tab;
        const params = new URLSearchParams();
        if (tab !== 'all') {
            params.set('type', tab);
        }
        const query = params.toString();
        router.get(query ? `/transactions?${query}` : '/transactions');
    }

    function goToPage(page: number) {
        const params = new URLSearchParams();
        if (activeTab !== 'all') {
            params.set('type', activeTab);
        }
        params.set('page', String(page));
        router.get(`/transactions?${params.toString()}`);
    }

    function handleSubmit() {
        createForm.post('/transactions', {
            onSuccess: () => {
                dialogOpen = false;
                createForm.reset();
            },
        });
    }

    function openEditModal(transaction: any) {
        editingTransaction = transaction;
        editForm.type = transaction.type;
        editForm.amount = transaction.amount;
        editForm.category = transaction.category;
        editForm.description = transaction.description || '';
        editForm.date = transaction.date.split(' ')[0];
        editForm.clearErrors();
        editDialogOpen = true;
    }

    function handleUpdate() {
        if (!editingTransaction) return;
        editForm.put(`/transactions/${editingTransaction.id}`, {
            onSuccess: () => {
                editDialogOpen = false;
                editingTransaction = null;
                editForm.reset();
            },
        });
    }

    function openDeleteDialog(transaction: any) {
        deletingTransaction = transaction;
        deleteDialogOpen = true;
    }

    function handleDelete() {
        if (!deletingTransaction) return;
        router.delete(`/transactions/${deletingTransaction.id}`, {
            onSuccess: () => {
                deleteDialogOpen = false;
                deletingTransaction = null;
            },
        });
    }
</script>

<AppHead title="المعاملات" />

<div class="flex h-full flex-1 flex-col gap-4 p-4">
    {#if flash.success}
        <div class="rounded-lg border border-mint/40 bg-mint/10 px-4 py-3 text-sm text-foreground animate-fade-in">
            ✓ {flash.success}
        </div>
    {/if}
    {#if flash.error}
        <div class="rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive animate-fade-in">
            {flash.error}
        </div>
    {/if}

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight text-foreground">حركاتي</h1>
        <Dialog bind:open={dialogOpen} onOpenChange={(v) => { if (!v) { createForm.reset(); createForm.clearErrors(); } }}>
            <DialogTrigger>
                {#snippet child({ props })}
                    <Button {...props} class="h-9 rounded-full bg-foreground font-label text-xs text-background hover:bg-foreground/90 transition-all hover:scale-105 active:scale-95">
                        <Plus class="ml-2 h-4 w-4" />
                        سجّل معاملة
                    </Button>
                {/snippet}
            </DialogTrigger>
            <DialogContent class="max-w-md rounded-2xl border-border bg-card">
                <DialogHeader>
                    <DialogTitle class="text-lg font-semibold">معاملة جديدة</DialogTitle>
                </DialogHeader>
                <form onsubmit={(e) => { e.preventDefault(); handleSubmit(); }} class="flex flex-col gap-5">
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            onclick={() => { createForm.type = 'expense'; createForm.category = ''; }}
                            class="rounded-xl border px-4 py-3 text-sm font-medium transition-colors {createForm.type === 'expense' ? 'border-foreground bg-foreground text-background' : 'border-border bg-background text-foreground hover:border-foreground/30'}"
                        >
                            مصروف
                        </button>
                        <button
                            type="button"
                            onclick={() => { createForm.type = 'income'; createForm.category = ''; }}
                            class="rounded-xl border px-4 py-3 text-sm font-medium transition-colors {createForm.type === 'income' ? 'border-foreground bg-foreground text-background' : 'border-border bg-background text-foreground hover:border-foreground/30'}"
                        >
                            دخل
                        </button>
                    </div>

                    <div class="grid gap-2">
                        <Label class="text-xs text-muted-foreground">المبلغ</Label>
                        <Input
                            type="number"
                            step="0.01"
                            min="0.01"
                            bind:value={createForm.amount}
                            placeholder="0"
                            class="h-12 rounded-xl border-border bg-background px-4 text-xl font-semibold"
                        />
                        {#if createForm.errors.amount}
                            <p class="text-xs text-destructive">{createForm.errors.amount}</p>
                        {/if}
                    </div>

                    <div class="grid gap-2">
                        <Label class="text-xs text-muted-foreground">الفئة</Label>
                        <Select type="single" bind:value={createForm.category}>
                            <SelectTrigger class="h-11 w-full rounded-xl border-border bg-background px-4">
                                {createForm.category ? currentCategories.find(c => c.value === createForm.category)?.label : 'اختر فئة'}
                            </SelectTrigger>
                            <SelectContent>
                                {#each currentCategories as cat}
                                    <SelectItem value={cat.value}>{cat.label}</SelectItem>
                                {/each}
                            </SelectContent>
                        </Select>
                        {#if createForm.errors.category}
                            <p class="text-xs text-destructive">{createForm.errors.category}</p>
                        {/if}
                    </div>

                    <div class="grid gap-2">
                        <Label class="text-xs text-muted-foreground">التاريخ</Label>
                        <Input
                            type="date"
                            bind:value={createForm.date}
                            class="h-11 rounded-xl border-border bg-background px-4"
                        />
                        {#if createForm.errors.date}
                            <p class="text-xs text-destructive">{createForm.errors.date}</p>
                        {/if}
                    </div>

                    <div class="grid gap-2">
                        <Label class="text-xs text-muted-foreground">ملاحظة <span class="text-muted-foreground/60">(اختياري)</span></Label>
                        <Textarea
                            bind:value={createForm.description}
                            placeholder="مثال: قهوة من ستاربكس..."
                            rows={2}
                            class="rounded-xl border-border bg-background resize-none"
                        />
                        {#if createForm.errors.description}
                            <p class="text-xs text-destructive">{createForm.errors.description}</p>
                        {/if}
                    </div>

                    <Button
                        type="submit"
                        class="h-11 w-full rounded-xl bg-foreground font-label text-xs text-background hover:bg-foreground/90 transition-all active:scale-[0.98]"
                        disabled={createForm.processing}
                    >
                        {createForm.processing ? 'جاري الحفظ...' : 'حفظ'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    </div>

    <div class="flex gap-2">
        <button
            onclick={() => switchTab('all')}
            class="rounded-full px-4 py-2 text-sm transition-all {activeTab === 'all' ? 'bg-foreground text-background' : 'border border-border text-foreground hover:border-foreground/30'}"
        >
            الكل
        </button>
        <button
            onclick={() => switchTab('expense')}
            class="rounded-full px-4 py-2 text-sm transition-all {activeTab === 'expense' ? 'bg-foreground text-background' : 'border border-border text-foreground hover:border-foreground/30'}"
        >
            مصروفات
        </button>
        <button
            onclick={() => switchTab('income')}
            class="rounded-full px-4 py-2 text-sm transition-all {activeTab === 'income' ? 'bg-foreground text-background' : 'border border-border text-foreground hover:border-foreground/30'}"
        >
            دخل
        </button>
    </div>

    <div class="rounded-xl border border-border bg-card">
        {#if transactions.data.length === 0}
            <div class="flex flex-col items-center justify-center py-12">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                    <span class="text-2xl opacity-50">📝</span>
                </div>
                <p class="mt-3 text-sm font-medium text-foreground">ما في حركات بعد</p>
                <p class="mt-1 text-xs text-muted-foreground">سجّل أول معاملة لك وابدأ تتبع مصاريفك</p>
            </div>
        {:else}
            <div class="divide-y divide-border">
                {#each transactions.data as transaction (transaction.id)}
                    <div class="flex items-center justify-between px-4 py-3 transition-colors hover:bg-muted/30">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg {transaction.type === 'income' ? 'bg-mint/20 text-mint' : 'bg-blush/20 text-blush'}">
                                <span class="text-base font-medium">{transaction.type === 'income' ? '↓' : '↑'}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    {categoryLabelMap[transaction.category] ?? transaction.category}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {formatDate(transaction.date)}
                                    {#if transaction.description}
                                        · {transaction.description}
                                    {/if}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold {transaction.type === 'income' ? 'text-mint' : 'text-foreground'}">
                                {transaction.type === 'income' ? '+' : '-'}{parseFloat(transaction.amount).toLocaleString('en-US', { maximumFractionDigits: 2 })} <span class="text-xs font-normal text-muted-foreground">ريال</span>
                            </p>
                            <div class="flex items-center gap-0.5">
                                <button
                                    onclick={() => openEditModal(transaction)}
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    onclick={() => openDeleteDialog(transaction)}
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </div>

    {#if transactions.last_page > 1}
        <div class="flex items-center justify-center gap-2">
            <button
                onclick={() => goToPage(transactions.current_page - 1)}
                disabled={transactions.current_page <= 1}
                class="rounded-full border border-border px-4 py-2 text-sm transition-all disabled:opacity-50 text-foreground hover:border-foreground/30 active:scale-95"
            >
                ← السابق
            </button>
            <span class="text-sm text-muted-foreground">{transactions.current_page} / {transactions.last_page}</span>
            <button
                onclick={() => goToPage(transactions.current_page + 1)}
                disabled={transactions.current_page >= transactions.last_page}
                class="rounded-full border border-border px-4 py-2 text-sm transition-all disabled:opacity-50 text-foreground hover:border-foreground/30 active:scale-95"
            >
                التالي →
            </button>
        </div>
    {/if}
</div>

<Dialog bind:open={editDialogOpen} onOpenChange={(v) => { if (!v) { editForm.reset(); editForm.clearErrors(); } }}>
    <DialogContent class="max-w-md rounded-2xl border-border bg-card">
        <DialogHeader>
            <DialogTitle class="text-lg font-semibold">تعديل المعاملة</DialogTitle>
        </DialogHeader>
        <form onsubmit={(e) => { e.preventDefault(); handleUpdate(); }} class="flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    onclick={() => { editForm.type = 'expense'; editForm.category = ''; }}
                    class="rounded-xl border px-4 py-3 text-sm font-medium transition-all {editForm.type === 'expense' ? 'border-foreground bg-foreground text-background' : 'border-border bg-background text-foreground hover:border-foreground/30'}"
                >
                    مصروف
                </button>
                <button
                    type="button"
                    onclick={() => { editForm.type = 'income'; editForm.category = ''; }}
                    class="rounded-xl border px-4 py-3 text-sm font-medium transition-all {editForm.type === 'income' ? 'border-foreground bg-foreground text-background' : 'border-border bg-background text-foreground hover:border-foreground/30'}"
                >
                    دخل
                </button>
            </div>

            <div class="grid gap-2">
                <Label class="text-xs text-muted-foreground">المبلغ</Label>
                <Input
                    type="number"
                    step="0.01"
                    min="0.01"
                    bind:value={editForm.amount}
                    placeholder="0"
                    class="h-12 rounded-xl border-border bg-background px-4 text-xl font-semibold"
                />
                {#if editForm.errors.amount}
                    <p class="text-xs text-destructive">{editForm.errors.amount}</p>
                {/if}
            </div>

            <div class="grid gap-2">
                <Label class="text-xs text-muted-foreground">الفئة</Label>
                <Select type="single" bind:value={editForm.category}>
                    <SelectTrigger class="h-11 w-full rounded-xl border-border bg-background px-4">
                        {editForm.category ? editCurrentCategories.find(c => c.value === editForm.category)?.label : 'اختر فئة'}
                    </SelectTrigger>
                    <SelectContent>
                        {#each editCurrentCategories as cat}
                            <SelectItem value={cat.value}>{cat.label}</SelectItem>
                        {/each}
                    </SelectContent>
                </Select>
                {#if editForm.errors.category}
                    <p class="text-xs text-destructive">{editForm.errors.category}</p>
                {/if}
            </div>

            <div class="grid gap-2">
                <Label class="text-xs text-muted-foreground">التاريخ</Label>
                <Input
                    type="date"
                    bind:value={editForm.date}
                    class="h-11 rounded-xl border-border bg-background px-4"
                />
                {#if editForm.errors.date}
                    <p class="text-xs text-destructive">{editForm.errors.date}</p>
                {/if}
            </div>

            <div class="grid gap-2">
                <Label class="text-xs text-muted-foreground">ملاحظة <span class="text-muted-foreground/60">(اختياري)</span></Label>
                <Textarea
                    bind:value={editForm.description}
                    placeholder="مثال: قهوة من ستاربكس..."
                    rows={2}
                    class="rounded-xl border-border bg-background resize-none"
                />
                {#if editForm.errors.description}
                    <p class="text-xs text-destructive">{editForm.errors.description}</p>
                {/if}
            </div>

            <Button
                type="submit"
                class="h-11 w-full rounded-xl bg-foreground font-label text-xs text-background hover:bg-foreground/90 transition-all active:scale-[0.98]"
                disabled={editForm.processing}
            >
                {editForm.processing ? 'جاري الحفظ...' : 'تحديث'}
            </Button>
        </form>
    </DialogContent>
</Dialog>

<AlertDialog bind:open={deleteDialogOpen}>
    <AlertDialogContent class="rounded-2xl border-border bg-card">
        <AlertDialogHeader>
            <AlertDialogTitle class="text-lg font-semibold">تبي تحذف هالمعاملة؟</AlertDialogTitle>
            <AlertDialogDescription class="text-sm text-muted-foreground">
                هالعملية ما تنفع ترجع بعدها.
            </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter class="gap-2">
            <AlertDialogCancel class="rounded-xl border-border">إلغاء</AlertDialogCancel>
            <AlertDialogAction
                onclick={handleDelete}
                class="rounded-xl bg-destructive text-destructive-foreground hover:bg-destructive/90 transition-all active:scale-95"
            >
                احذف
            </AlertDialogAction>
        </AlertDialogFooter>
    </AlertDialogContent>
</AlertDialog>
