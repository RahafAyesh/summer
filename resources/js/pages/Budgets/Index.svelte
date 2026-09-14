<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            {
                title: 'ميزانيتي',
                href: '/budgets',
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
    import Plus from '@lucide/svelte/icons/plus';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import AlertTriangle from '@lucide/svelte/icons/alert-triangle';
    import AlertCircle from '@lucide/svelte/icons/alert-circle';
    import Trash2 from '@lucide/svelte/icons/trash-2';

    let { budgets, expenses, currentMonth, categories, flash } = $props();

    let dialogOpen = $state(false);
    let deleteDialogOpen = $state(false);
    let deletingBudget = $state<{ id: number; category: string } | null>(null);

    const form = useForm({ category: '', amount: '', month: currentMonth });

    const categoryLabelMap = $derived(Object.fromEntries(categories.expenses.map((c: any) => [c.value, c.label])));
    const categoryColorMap = $derived(Object.fromEntries(categories.expenses.map((c: any) => [c.value, c.color])));

    function getBudgetForCategory(category: string) {
        return budgets.find((b: any) => b.category === category);
    }

    function getSpentForCategory(category: string): number {
        return parseFloat(expenses[category] ?? 0);
    }

    function getProgress(category: string): number {
        const budget = getBudgetForCategory(category);
        if (!budget) return 0;
        const spent = getSpentForCategory(category);
        return Math.min(100, (spent / parseFloat(budget.amount)) * 100);
    }

    function getRemaining(category: string): number {
        const budget = getBudgetForCategory(category);
        if (!budget) return 0;
        return parseFloat(budget.amount) - getSpentForCategory(category);
    }

    function getAlertStatus(category: string): 'safe' | 'warning' | 'danger' {
        const progress = getProgress(category);
        if (progress >= 100) return 'danger';
        if (progress >= 75) return 'warning';
        return 'safe';
    }

    function formatMonth(month: string): string {
        const [year, m] = month.split('-');
        const date = new Date(parseInt(year), parseInt(m) - 1);
        return date.toLocaleDateString('ar-SA', { year: 'numeric', month: 'long' });
    }

    function navigateMonth(direction: number) {
        const [year, month] = currentMonth.split('-').map(Number);
        const date = new Date(year, month - 1 + direction);
        const newMonth = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        router.get(`/budgets?month=${newMonth}`);
    }

    function handleSubmit(e: Event) {
        e.preventDefault();
        form.post('/budgets', {
            onSuccess: () => {
                dialogOpen = false;
                form.reset();
            },
        });
    }

    function openDeleteDialog(budget: { id: number; category: string }) {
        deletingBudget = budget;
        deleteDialogOpen = true;
    }

    function handleDelete() {
        if (!deletingBudget) return;
        router.delete(`/budgets/${deletingBudget.id}`, {
            onSuccess: () => {
                deleteDialogOpen = false;
                deletingBudget = null;
            },
        });
    }

    const categoriesWithBudget = $derived(
        categories.expenses.map((cat: any) => ({
            ...cat,
            budget: getBudgetForCategory(cat.value),
            spent: getSpentForCategory(cat.value),
            progress: getProgress(cat.value),
            remaining: getRemaining(cat.value),
            alertStatus: getAlertStatus(cat.value),
        }))
    );

    const warningCount = $derived(
        categoriesWithBudget.filter((c: any) => c.alertStatus === 'warning').length
    );
    const dangerCount = $derived(
        categoriesWithBudget.filter((c: any) => c.alertStatus === 'danger').length
    );
</script>

<AppHead title="ميزانيتي" />

<div class="flex h-full flex-1 flex-col gap-4 p-4">
    {#if flash?.success}
        <div class="rounded-lg border border-mint/40 bg-mint/10 px-4 py-3 animate-fade-in">
            <p class="text-sm text-foreground">✓ {flash.success}</p>
        </div>
    {/if}
    {#if flash?.error}
        <div class="rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 animate-fade-in">
            <p class="text-sm text-destructive">{flash.error}</p>
        </div>
    {/if}

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">ميزانيتي</h1>
            <div class="flex items-center gap-1.5">
                <button
                    onclick={() => navigateMonth(-1)}
                    class="flex h-7 w-7 items-center justify-center rounded-full border border-border hover:border-foreground/30 transition-colors"
                >
                    <ChevronRight class="h-3.5 w-3.5" />
                </button>
                <span class="text-xs text-muted-foreground min-w-[100px] text-center">{formatMonth(currentMonth)}</span>
                <button
                    onclick={() => navigateMonth(1)}
                    class="flex h-7 w-7 items-center justify-center rounded-full border border-border hover:border-foreground/30 transition-colors"
                >
                    <ChevronLeft class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
        <Dialog bind:open={dialogOpen} onopenchange={(open) => { if (open) form.month = currentMonth; }}>
            <DialogTrigger>
                {#snippet child({ props })}
                    <Button {...props} class="h-9 rounded-full bg-foreground text-xs text-background hover:bg-foreground/90 transition-all hover:scale-105 active:scale-95">
                        <Plus class="ml-1.5 h-3.5 w-3.5" />
                        حدد حد
                    </Button>
                {/snippet}
            </DialogTrigger>
            <DialogContent class="max-w-md rounded-2xl border-border bg-card">
                <DialogHeader>
                    <DialogTitle class="text-lg font-semibold">حدد ميزانية</DialogTitle>
                </DialogHeader>
                <form onsubmit={handleSubmit} class="flex flex-col gap-4">
                    <div class="grid gap-2">
                        <Label class="text-xs text-muted-foreground">الفئة</Label>
                        <Select type="single" bind:value={form.category} required>
                            <SelectTrigger class="h-11 w-full rounded-xl border-border bg-background px-4">
                                {form.category ? categoryLabelMap[form.category] : 'اختر فئة'}
                            </SelectTrigger>
                            <SelectContent>
                                {#each categories.expenses as cat}
                                    <SelectItem value={cat.value}>{cat.label}</SelectItem>
                                {/each}
                            </SelectContent>
                        </Select>
                        {#if form.errors.category}
                            <p class="text-xs text-destructive">{form.errors.category}</p>
                        {/if}
                    </div>

                    <div class="grid gap-2">
                        <Label class="text-xs text-muted-foreground">المبلغ</Label>
                        <Input
                            type="number"
                            step="0.01"
                            min="0"
                            bind:value={form.amount}
                            placeholder="0"
                            required
                            class="h-12 rounded-xl border-border bg-background px-4 text-xl font-semibold"
                        />
                        {#if form.errors.amount}
                            <p class="text-xs text-destructive">{form.errors.amount}</p>
                        {/if}
                    </div>

                    {#if form.errors.month}
                        <p class="text-xs text-destructive">{form.errors.month}</p>
                    {/if}

                    <Button
                        type="submit"
                        class="h-11 w-full rounded-xl bg-foreground text-xs text-background hover:bg-foreground/90 transition-all active:scale-[0.98]"
                        disabled={form.processing}
                    >
                        {form.processing ? 'جاري الحفظ...' : 'حفظ'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    </div>

    {#if warningCount > 0 || dangerCount > 0}
        <div class="rounded-xl border border-amber/40 bg-amber/10 p-3 animate-fade-in">
            <div class="flex items-center gap-2">
                <AlertTriangle class="h-4 w-4 shrink-0 text-amber" />
                <p class="text-sm text-foreground">
                    {#if dangerCount > 0}
                        <span class="font-semibold">{dangerCount}</span> {dangerCount === 1 ? 'ميزانية انفجرت' : 'ميزانيات انفجرت'}!
                    {:else}
                        <span class="font-semibold">{warningCount}</span> {warningCount === 1 ? 'ميزانية قربت تخلص' : 'ميزانيات قربت تخلص'}
                    {/if}
                </p>
            </div>
        </div>
    {/if}

    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
        {#each categoriesWithBudget as cat (cat.value)}
            <div class="rounded-xl border bg-card p-4 transition-all hover:shadow-sm {cat.alertStatus === 'danger' ? 'border-destructive/50' : cat.alertStatus === 'warning' ? 'border-amber/50' : 'border-border'}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg" style="background-color: {(categoryColorMap[cat.value] ?? '#666') + '20'}">
                            <span class="text-base">
                                {#if cat.value === 'food'}🍽️
                                {:else if cat.value === 'transport'}🚗
                                {:else if cat.value === 'bills'}📄
                                {:else if cat.value === 'health'}💊
                                {:else if cat.value === 'entertainment'}🎬
                                {:else if cat.value === 'shopping'}🛍️
                                {:else if cat.value === 'education'}📚
                                {:else}📦
                                {/if}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-foreground">{cat.label}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        {#if cat.alertStatus === 'danger'}
                            <div class="flex h-5 w-5 items-center justify-center rounded-full bg-destructive/20">
                                <AlertCircle class="h-3 w-3 text-destructive" />
                            </div>
                        {:else if cat.alertStatus === 'warning'}
                            <div class="flex h-5 w-5 items-center justify-center rounded-full bg-amber/20">
                                <AlertTriangle class="h-3 w-3 text-amber" />
                            </div>
                        {/if}
                        {#if cat.budget}
                            <button
                                onclick={() => openDeleteDialog(cat.budget)}
                                class="flex h-6 w-6 items-center justify-center rounded-lg text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
                        {/if}
                    </div>
                </div>

                {#if cat.budget}
                    <div class="mt-3">
                        <div class="flex items-baseline justify-between">
                            <p class="text-xl font-bold text-foreground">
                                {cat.spent.toLocaleString('en-US', { maximumFractionDigits: 0 })} <span class="text-xs font-normal text-muted-foreground">ريال</span>
                            </p>
                            <p class="text-xs text-muted-foreground">
                                من {parseFloat(cat.budget.amount).toLocaleString('en-US', { maximumFractionDigits: 0 })}
                            </p>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full transition-all duration-500 {cat.alertStatus === 'danger' ? 'bg-destructive' : cat.alertStatus === 'warning' ? 'bg-amber' : 'bg-mint'}"
                                style="width: {cat.progress}%"
                            ></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between">
                            <p class="text-xs {cat.alertStatus === 'danger' ? 'text-destructive font-medium' : cat.alertStatus === 'warning' ? 'text-amber' : 'text-muted-foreground'}">
                                {#if cat.remaining >= 0}
                                    باقي {cat.remaining.toLocaleString('en-US', { maximumFractionDigits: 0 })} ريال
                                {:else}
                                    زدت {Math.abs(cat.remaining).toLocaleString('en-US', { maximumFractionDigits: 0 })} ريال 😬
                                {/if}
                            </p>
                            <p class="text-xs text-muted-foreground">{cat.progress.toFixed(0)}%</p>
                        </div>
                    </div>
                {:else}
                    <div class="mt-3 flex items-center justify-center py-3">
                        <p class="text-xs text-muted-foreground">ما حددت ميزانية</p>
                    </div>
                {/if}
            </div>
        {/each}
    </div>
</div>

<AlertDialog bind:open={deleteDialogOpen}>
    <AlertDialogContent class="rounded-2xl border-border bg-card">
        <AlertDialogHeader>
            <AlertDialogTitle class="text-lg font-semibold">تبي تحذف هالميزانية؟</AlertDialogTitle>
            <AlertDialogDescription class="text-sm text-muted-foreground">
                ميزانية {deletingBudget ? categoryLabelMap[deletingBudget.category] : ''} ما راح تنفع ترجع.
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
