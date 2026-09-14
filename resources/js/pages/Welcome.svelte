<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login } from '@/routes';
    import { register } from '@/routes';
    import Sparkles from '@lucide/svelte/icons/sparkles';
    import Wallet from '@lucide/svelte/icons/wallet';
    import BarChart3 from '@lucide/svelte/icons/bar-chart-3';
    import Shield from '@lucide/svelte/icons/shield';

    const auth = $derived(page.props.auth);

    const features = [
        {
            icon: Wallet,
            title: 'تتبع المصروفات',
            description: 'سجل مصاريفك ودخلك بسهولة وتابع أين تذهب أموالك',
            color: 'bg-lavender/20',
        },
        {
            icon: BarChart3,
            title: 'ميزانيات شهرية',
            description: 'ضع ميزانيات لكل فئة واحصل على تنبيهات عند الاقتراب من الحد',
            color: 'bg-mint/20',
        },
        {
            icon: Sparkles,
            title: 'مساعد ذكي',
            description: 'اسأل المساعد الذكي عن مصاريفك واحصل على نصائح للتوفير',
            color: 'bg-blush/20',
        },
        {
            icon: Shield,
            title: 'آمن وموثوق',
            description: 'بياناتك مشفرة ومحمية في السحابة',
            color: 'bg-sky/20',
        },
    ];
</script>

<AppHead title="مصاريفي - تتبع مصاريفك بذكاء" />

<div class="min-h-screen bg-background">
    <header class="border-b border-border">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-lavender/20">
                    <span class="text-xl font-bold text-foreground">م</span>
                </div>
                <span class="text-lg font-semibold text-foreground">مصاريفي</span>
            </div>
            <div class="flex items-center gap-3">
                {#if !auth.user}
                    <Link
                        href={toUrl(login())}
                        class="font-label text-xs text-foreground hover:text-foreground/70"
                    >
                        تسجيل الدخول
                    </Link>
                    <Link
                        href={toUrl(register())}
                        class="rounded-full bg-foreground px-5 py-2 font-label text-xs text-background hover:bg-foreground/90"
                    >
                        إنشاء حساب
                    </Link>
                {/if}
            </div>
        </nav>
    </header>

    <main>
        <section class="mx-auto max-w-6xl px-6 py-20 text-center">
            <div class="mx-auto max-w-3xl">
                <p class="font-label text-xs text-muted-foreground">تطبيق تتبع المصاريف</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight text-foreground md:text-5xl">
                    تتبع مصاريفك
                    <span class="text-lavender">بذكاء</span>
                </h1>
                <p class="mt-6 text-lg text-muted-foreground">
                    تطبيق ذكي يساعدك على تتبع مصاريفك ودخلك، وضع ميزانيات شهرية، والحصول على نصائح مخصصة لتحسين وضعك المالي
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    {#if !auth.user}
                        <Link
                            href={toUrl(register())}
                            class="rounded-full bg-foreground px-8 py-3 font-label text-xs text-background hover:bg-foreground/90"
                        >
                            ابدأ مجاناً
                        </Link>
                        <Link
                            href={toUrl(login())}
                            class="rounded-full border border-border px-8 py-3 font-label text-xs text-foreground hover:border-foreground/30"
                        >
                            تسجيل الدخول
                        </Link>
                    {:else}
                        <Link
                            href={toUrl(dashboard())}
                            class="rounded-full bg-foreground px-8 py-3 font-label text-xs text-background hover:bg-foreground/90"
                        >
                            لوحة التحكم
                        </Link>
                    {/if}
                </div>
            </div>
        </section>

        <section class="border-t border-border bg-card py-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="text-center">
                    <p class="font-label text-xs text-muted-foreground">المميزات</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground">
                        كل ما تحتاجه لإدارة أموالك
                    </h2>
                </div>
                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {#each features as feature}
                        <div class="rounded-2xl border border-border bg-background p-6">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl {feature.color}">
                                <feature.icon class="h-6 w-6 text-foreground" />
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-foreground">{feature.title}</h3>
                            <p class="mt-2 text-sm text-muted-foreground">{feature.description}</p>
                        </div>
                    {/each}
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="rounded-3xl bg-lavender/10 p-8 text-center md:p-12">
                <h2 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">
                    ابدأ رحلة التحكم في أموالك اليوم
                </h2>
                <p class="mt-4 text-muted-foreground">
                    انضم إلى آلاف المستخدمين الذين يحسنون وضعهم المالي مع مصاريفي
                </p>
                {#if !auth.user}
                    <Link
                        href={toUrl(register())}
                        class="mt-8 inline-block rounded-full bg-foreground px-8 py-3 font-label text-xs text-background hover:bg-foreground/90"
                    >
                        أنشئ حسابك المجاني
                    </Link>
                {/if}
            </div>
        </section>
    </main>

    <footer class="border-t border-border py-8">
        <div class="mx-auto max-w-6xl px-6 text-center">
            <p class="text-sm text-muted-foreground">
                مصاريفي - تطبيق تتبع المصاريف الذكي
            </p>
        </div>
    </footer>
</div>
