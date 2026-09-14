<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import LayoutGrid from '@lucide/svelte/icons/layout-grid';
    import ArrowLeftRight from '@lucide/svelte/icons/arrow-left-right';
    import Wallet from '@lucide/svelte/icons/wallet';
    import MessageCircle from '@lucide/svelte/icons/message-circle';
    import Settings from '@lucide/svelte/icons/settings';
    import type { Snippet } from 'svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
        useSidebar,
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const sidebar = useSidebar();

    function handleNavClick() {
        if (sidebar.isMobile) {
            sidebar.setOpenMobile(false);
        }
    }

    const mainNavItems: NavItem[] = [
        {
            title: 'لوحة التحكم',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'المعاملات',
            href: '/transactions',
            icon: ArrowLeftRight,
        },
        {
            title: 'الميزانيات',
            href: '/budgets',
            icon: Wallet,
        },
        {
            title: 'المساعد الذكي',
            href: '/assistant',
            icon: MessageCircle,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'الإعدادات',
            href: '#',
            icon: Settings,
        },
    ];
</script>

<Sidebar collapsible="icon" variant="inset" side="right">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg">
                    {#snippet child({ props })}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class="flex items-center gap-3"
                            onclick={handleNavClick}
                        >
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-lavender/20">
                                <span class="text-lg font-bold text-foreground">م</span>
                            </div>
                            <span class="text-base font-semibold text-foreground">مصاريفي</span>
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavMain items={footerNavItems} />
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
