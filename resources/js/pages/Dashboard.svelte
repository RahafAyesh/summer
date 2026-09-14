<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'نظرة عامة',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Link, router } from '@inertiajs/svelte';
    import AlertTriangle from '@lucide/svelte/icons/alert-triangle';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';

    interface CategoryOption {
        value: string;
        label: string;
        color: string;
    }

    interface Transaction {
        id: number;
        type: string;
        amount: string;
        category: string;
        description: string | null;
        date: string;
    }

    let {
        categories,
        totalIncome,
        totalExpenses,
        netBalance,
        savingsRate,
        expensesByCategory,
        recentTransactions,
        budgetAlerts,
        transactionCount,
        currentMonth,
    }: {
        categories: {
            expenses: CategoryOption[];
            incomes: CategoryOption[];
        };
        totalIncome: number;
        totalExpenses: number;
        netBalance: number;
        savingsRate: number;
        expensesByCategory: Record<string, number>;
        recentTransactions: Transaction[];
        budgetAlerts: number;
        transactionCount: number;
        currentMonth: string;
    } = $props();

    const allCategories = $derived([...categories.expenses, ...categories.incomes]);
    const expenseLabelMap = $derived(Object.fromEntries(allCategories.map((c) => [c.value, c.label])));
    const expenseColorMap = $derived(Object.fromEntries(categories.expenses.map((c) => [c.value, c.color])));

    function formatCurrency(amount: number | string): string {
        const num = parseFloat(amount.toString());
        if (num >= 1000) {
            return num.toLocaleString('en-US', { maximumFractionDigits: 0 });
        }
        return num % 1 === 0 ? num.toString() : num.toFixed(2);
    }

    function formatDate(dateStr: string): string {
        const date = new Date(dateStr);
        return date.toLocaleDateString('ar-SA', { month: 'short', day: 'numeric' });
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
        router.get(`/dashboard?month=${newMonth}`);
    }

    const maxExpense = $derived(
        Math.max(...Object.values(expensesByCategory).map(Number), 1)
    );
</script>

<AppHead title="لوحة التحكم" />

    <div class="flex h-full flex-1 flex-col gap-5 p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">نظرة عامة</h1>
            <div class="flex items-center gap-2">
                <button
                    onclick={() => navigateMonth(-1)}
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-border hover:border-foreground/30"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
                <span class="font-label text-xs text-muted-foreground">{formatMonth(currentMonth)}</span>
                <button
                    onclick={() => navigateMonth(1)}
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-border hover:border-foreground/30"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>

    {#if budgetAlerts > 0}
        <Link href="/budgets" class="rounded-xl border border-amber/40 bg-amber/10 p-4 block hover:border-amber/60 transition-all hover:scale-[1.01] active:scale-[0.99]">
            <div class="flex items-center gap-3">
                <AlertTriangle class="h-5 w-5 shrink-0 text-amber" />
                <p class="text-sm text-foreground">
                    انتبه! <span class="font-semibold">{budgetAlerts}</span> {budgetAlerts === 1 ? 'ميزانية قربت تخلص' : 'ميزانيات قربت تخلص'}
                </p>
            </div>
        </Link>
    {/if}

    <Link href="/assistant" class="rounded-xl border border-lavender/40 bg-lavender/10 p-4 block hover:border-lavender/60 transition-all hover:scale-[1.01] active:scale-[0.99]">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-lavender/20">
                <span class="text-xl">✨</span>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-foreground">اسأل المساعد</p>
                <p class="text-xs text-muted-foreground">"كم صرفت هالشهر؟" أو "أضف 50 ريال قهوة"</p>
            </div>
        </div>
    </Link>

    <div class="grid gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-border bg-card p-4 transition-all hover:shadow-sm">
            <p class="text-xs text-muted-foreground">رصيدك</p>
            <p class="mt-1 text-2xl font-bold {netBalance >= 0 ? 'text-foreground' : 'text-destructive'}">
                {formatCurrency(netBalance)} <span class="text-sm font-normal text-muted-foreground">ريال</span>
            </p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 transition-all hover:shadow-sm">
            <p class="text-xs text-muted-foreground">دخلت</p>
            <p class="mt-1 text-2xl font-bold text-mint">
                +{formatCurrency(totalIncome)} <span class="text-sm font-normal text-muted-foreground">ريال</span>
            </p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 transition-all hover:shadow-sm">
            <p class="text-xs text-muted-foreground">صرفت</p>
            <p class="mt-1 text-2xl font-bold text-blush">
                -{formatCurrency(totalExpenses)} <span class="text-sm font-normal text-muted-foreground">ريال</span>
            </p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 transition-all hover:shadow-sm">
            <p class="text-xs text-muted-foreground">ادّخرت</p>
            <p class="mt-1 text-2xl font-bold text-foreground">
                {savingsRate}<span class="text-sm font-normal text-muted-foreground">%</span>
            </p>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-border bg-card p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-foreground">آخر حركاتك</h3>
                <Link href="/transactions" class="text-xs text-lavender hover:underline">الكل ←</Link>
            </div>
            {#if recentTransactions.length === 0}
                <div class="flex flex-col items-center justify-center py-6">
                    <p class="text-sm text-muted-foreground">ما عندك حركات بعد</p>
                    <Link href="/transactions" class="mt-2 text-xs text-lavender hover:underline">سجّل أول معاملة</Link>
                </div>
            {:else}
                <div class="space-y-2">
                    {#each recentTransactions as transaction}
                        <div class="flex items-center justify-between py-1">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg {transaction.type === 'income' ? 'bg-mint/20' : 'bg-blush/20'}">
                                    <span class="text-sm">{transaction.type === 'income' ? '↓' : '↑'}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-foreground">{expenseLabelMap[transaction.category] ?? transaction.category}</p>
                                    <p class="text-xs text-muted-foreground">{formatDate(transaction.date)}</p>
                                </div>
                            </div>
                            <p class="text-sm font-semibold {transaction.type === 'income' ? 'text-mint' : 'text-foreground'}">
                                {transaction.type === 'income' ? '+' : '-'}{formatCurrency(transaction.amount)} <span class="text-xs font-normal text-muted-foreground">ريال</span>
                            </p>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>

        <div class="rounded-xl border border-border bg-card p-4">
            <h3 class="text-sm font-medium text-foreground mb-3">وين راح فلوسك؟</h3>
            {#if Object.keys(expensesByCategory).length === 0}
                <div class="flex flex-col items-center justify-center py-6">
                    <p class="text-sm text-muted-foreground">ما صرفت شي هالشهر 🎉</p>
                </div>
            {:else}
                <div class="space-y-2">
                    {#each Object.entries(expensesByCategory) as [category, amount]}
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-foreground">{expenseLabelMap[category] ?? category}</span>
                                <span class="text-muted-foreground">{formatCurrency(amount)} ريال</span>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    style="width: {(parseFloat(amount.toString()) / maxExpense) * 100}%; background-color: {expenseColorMap[category] ?? '#6b7280'}"
                                ></div>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>
    </div>

    <div class="rounded-xl border border-border bg-card p-4">
        <h3 class="text-sm font-medium text-foreground mb-3">إحصائيات الشهر</h3>
        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg bg-lavender/10 p-3 text-center">
                <p class="text-2xl font-bold text-foreground">{transactionCount}</p>
                <p class="mt-1 text-xs text-muted-foreground">معاملة</p>
            </div>
            <div class="rounded-lg bg-mint/10 p-3 text-center">
                <p class="text-2xl font-bold text-foreground">
                    {transactionCount > 0 ? (parseFloat(totalIncome.toString()) / transactionCount).toFixed(0) : '0'}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">متوسط الدخل</p>
            </div>
            <div class="rounded-lg bg-blush/10 p-3 text-center">
                <p class="text-2xl font-bold text-foreground">
                    {Object.keys(expensesByCategory).length > 0 ? (parseFloat(totalExpenses.toString()) / Object.keys(expensesByCategory).length).toFixed(0) : '0'}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">متوسط المصروف/فئة</p>
            </div>
        </div>
    </div>
</div>
