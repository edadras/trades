<script setup>
// Authenticated shell: sidebar on desktop, top bar + bottom navigation + drawer on mobile.
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Logo from '@/Components/ui/Logo.vue';
import Icon from '@/Components/ui/Icon.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Drawer from '@/Components/ui/Drawer.vue';
import LocaleSwitch from '@/Components/ui/LocaleSwitch.vue';
import FlashToast from '@/Components/ui/FlashToast.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import MobileTabBar from '@/Components/ui/MobileTabBar.vue';
import { isCurrentPage, route, useI18n } from '@/i18n';

defineProps({ title: String, subtitle: String, back: String, wide: Boolean });
const page = usePage();
const { t } = useI18n();
const user = computed(() => page.props.auth.user);
const can = (p) => user.value?.permissions?.includes(p);
const has = (r) => user.value?.roles?.includes(r);
const drawer = ref(false);
const userMenu = ref(false);

const groups = computed(() => {
    const g = [];
    if (has('business') && user.value?.has_business) {
        g.push({ title: user.value.businesses?.length > 1 ? user.value.business?.name : t('nav.group_business'), switcher: user.value.businesses?.length > 1, items: [
            { label: t('nav.dashboard'), icon: 'home', href: route('dashboard'), match: 'dashboard', primary: true },
            { label: t('nav.cases'), icon: 'folder', href: route('cases.index'), match: 'cases.*', primary: true },
            ...(user.value.can_create_case ? [{ label: t('nav.new_case'), short: t('nav.new_case_short'), icon: 'plus', href: route('cases.create'), match: 'cases.create', primary: true, cta: true }] : []),
            { label: t('nav.learning'), short: t('nav.learning_short'), icon: 'book', href: route('learning'), match: 'learning', primary: true },
            { label: t('nav.business_profile'), icon: 'briefcase', href: route('business.profile'), match: 'business.profile' },
            { label: t('nav.team'), icon: 'users', href: route('business.team.index'), match: 'business.team.*' },
        ] });
    }
    if (has('supporter') || user.value?.expert_status) {
        g.push({ title: t('nav.group_expert'), items: [
            ...(has('supporter') ? [
                { label: t('nav.dashboard'), icon: 'home', href: route('expert.dashboard'), match: 'expert.dashboard', primary: !has('business') },
                { label: t('nav.invitations'), short: t('nav.invitations'), icon: 'inbox', href: route('expert.invitations.index'), match: 'expert.invitations.*', primary: !has('business') },
                { label: t('nav.my_cases'), short: t('nav.cases'), icon: 'folder', href: route('expert.cases.index'), match: 'expert.cases.*', primary: !has('business') },
            ] : []),
            { label: t('nav.expert_profile'), short: t('nav.profile'), icon: 'user', href: route('expert.profile.edit'), match: 'expert.profile.*', primary: !has('supporter') && !has('business') },
        ] });
    }
    if (user.value?.is_staff) {
        const items = [];
        if (can('analytics.view')) items.push({ label: t('nav.admin_dashboard'), short: t('nav.dashboard'), icon: 'chart', href: route('admin.dashboard'), match: 'admin.dashboard', primary: true });
        if (can('cases.review')) items.push({ label: t('nav.review_queue'), short: t('nav.review_short'), icon: 'inbox', href: route('review.index'), match: 'review.index', primary: true, badge: page.props.auth.review_queue }, { label: t('nav.all_cases'), short: t('nav.cases'), icon: 'folder', href: route('review.cases.index'), match: 'review.cases.*', primary: true });
        if (can('kpis.manage')) items.push({ label: t('nav.kpis'), icon: 'target', href: route('admin.kpis.index'), match: 'admin.kpis.*' });
        if (can('experts.view')) items.push({ label: t('nav.experts'), icon: 'users', href: route('admin.experts.index'), match: 'admin.experts.*' });
        if (can('businesses.view')) items.push({ label: t('nav.businesses'), icon: 'briefcase', href: route('admin.businesses.index'), match: 'admin.businesses.*' });
        if (can('pilot.manage') || can('reports.view')) items.push({ label: t('nav.pilot'), short: t('nav.pilot_short'), icon: 'flag', href: route('admin.pilot.show'), match: 'admin.pilot.*', primary: !can('cases.review') });
        if (can('knowledge.manage') || can('knowledge.approve')) items.push({ label: t('nav.knowledge_admin'), short: t('nav.knowledge_short'), icon: 'book', href: route('admin.knowledge.index'), match: 'admin.knowledge.*', primary: !can('cases.review') });
        if (can('knowledge.manage')) items.push({ label: t('nav.knowledge_taxonomy'), icon: 'layers', href: route('admin.taxonomy.index'), match: 'admin.taxonomy.*' });
        if (can('partners.manage')) items.push({ label: t('nav.partners'), icon: 'network', href: route('admin.partners.index'), match: 'admin.partners.*' });
        if (can('legal.review') || can('data_requests.manage') || can('complaints.manage')) items.push({ label: t('nav.compliance'), short: t('nav.compliance_short'), icon: 'scale', href: route('admin.compliance.index'), match: 'admin.compliance.*', primary: can('legal.review') || (!can('cases.review') && !can('analytics.view')) });
        if (can('categories.manage')) items.push({ label: t('nav.categories'), icon: 'layers', href: route('admin.categories.index'), match: 'admin.categories.*' });
        if (can('users.manage')) items.push({ label: t('nav.users'), icon: 'key', href: route('admin.users.index'), match: 'admin.users.*' });
        if (can('audit.view')) items.push({ label: t('nav.audit'), icon: 'shield', href: route('admin.audit.index'), match: 'admin.audit.*' });
        g.push({ title: t('nav.group_staff'), items });
    }
    return g;
});
// Bottom tab bar: the role's primary destinations first, topped up with the next items so it always has four.
const bottom = computed(() => {
    const all = groups.value.flatMap((g) => g.items);
    return [...all.filter((i) => i.primary), ...all.filter((i) => !i.primary)].slice(0, 4);
});
const isActive = (m, href) => isCurrentPage(m, href);
const logout = () => router.post(route('logout'));
const switchBusiness = (id) => router.post(route('business.switch'), { business_id: id });
</script>

<template>
    <div class="min-h-screen bg-[var(--surface-muted)]">
        <SeoHead :title="title ? `${title} · ${$t('app.name')}` : $t('app.name')" />
        <!-- Desktop sidebar -->
        <aside class="fixed inset-y-0 start-0 z-30 hidden w-72 flex-col border-e border-[var(--border)] bg-white lg:flex">
            <div class="flex h-20 items-center px-6"><Link :href="route('home')"><Logo /></Link></div>
            <nav class="flex-1 space-y-6 overflow-y-auto px-4 pb-6">
                <div v-for="g in groups" :key="g.title">
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ g.title }}</p>
                    <select v-if="g.switcher" class="input mb-2 py-2 text-sm" :value="user.business?.id" :aria-label="$t('nav.switch_business')" @change="switchBusiness($event.target.value)">
                        <option v-for="b in user.businesses" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <ul class="space-y-1">
                        <li v-for="item in g.items" :key="item.href">
                            <Link :href="item.href" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition" :class="isActive(item.match, item.href) ? 'bg-navy-950 text-white shadow' : item.cta ? 'text-navy-800 ring-1 ring-navy-200 hover:bg-navy-50' : 'text-gray-600 hover:bg-navy-50 hover:text-ink'">
                                <Icon :name="item.icon" :size="18" />
                                <span class="flex-1">{{ item.label }}</span>
                                <span v-if="item.badge" class="rounded-full bg-amber-400 px-2 text-xs font-semibold text-navy-950">{{ item.badge }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="border-t border-[var(--border)] p-4">
                <Link :href="route('settings.profile')" class="flex items-center gap-3 rounded-2xl p-2 hover:bg-navy-50">
                    <Avatar :name="user?.name" size="sm" />
                    <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ user?.name }}</p><p class="truncate text-xs text-gray-500">{{ user?.email }}</p></div>
                    <Icon name="settings" :size="16" class="text-gray-400" />
                </Link>
            </div>
        </aside>

        <div class="lg:ps-72">
            <!-- Top bar -->
            <header class="sticky top-0 z-20 border-b border-[var(--border)] bg-white/90 backdrop-blur">
                <div class="flex h-16 items-center gap-2 px-4 sm:px-6 lg:h-20 lg:px-10">
                    <button type="button" class="grid size-10 place-items-center rounded-full hover:bg-gray-100 lg:hidden" :aria-label="$t('nav.menu')" @click="drawer = true"><Icon name="menu" /></button>
                    <Link :href="route('home')" class="lg:hidden"><Logo compact /></Link>
                    <div class="min-w-0 flex-1">
                        <Link v-if="back" :href="back" class="hidden items-center gap-1 text-xs text-gray-500 hover:text-ink sm:inline-flex"><Icon name="arrow-left" :size="14" />{{ $t('common.back') }}</Link>
                        <h1 class="truncate text-base font-semibold text-ink sm:text-lg lg:text-xl">{{ title }}</h1>
                    </div>
                    <slot name="header-actions" />
                    <span class="hidden sm:block"><LocaleSwitch /></span>
                    <Link :href="route('notifications.index')" class="relative grid size-10 place-items-center rounded-full hover:bg-gray-100" :aria-label="$t('nav.notifications')">
                        <Icon name="bell" />
                        <span v-if="page.props.auth.unread_notifications" class="absolute end-1.5 top-1.5 grid min-w-[18px] place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white">{{ page.props.auth.unread_notifications > 9 ? '9+' : page.props.auth.unread_notifications }}</span>
                    </Link>
                    <div class="relative">
                        <button type="button" class="rounded-full" :aria-label="$t('nav.account')" @click="userMenu = !userMenu"><Avatar :name="user?.name" size="sm" /></button>
                        <div v-if="userMenu" class="absolute end-0 mt-2 w-56 rounded-2xl bg-white p-2 shadow-xl ring-1 ring-[var(--border)]" @click="userMenu = false">
                            <Link :href="route('settings.profile')" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-navy-50"><Icon name="user" :size="16" />{{ $t('nav.profile') }}</Link>
                            <Link :href="route('settings.security')" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-navy-50"><Icon name="lock" :size="16" />{{ $t('nav.security') }}</Link>
                            <Link :href="route('settings.notifications')" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-navy-50"><Icon name="bell" :size="16" />{{ $t('nav.notification_settings') }}</Link>
                            <Link :href="route('settings.privacy')" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-navy-50"><Icon name="shield" :size="16" />{{ $t('nav.privacy') }}</Link>
                            <Link :href="route('support.index')" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-navy-50"><Icon name="info" :size="16" />{{ $t('nav.support') }}</Link>
                            <button type="button" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50" @click="logout"><Icon name="logout" :size="16" />{{ $t('nav.logout') }}</button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto px-4 py-6 pb-28 sm:px-6 lg:px-10 lg:py-8 lg:pb-10" :class="wide ? 'max-w-[1500px]' : 'max-w-6xl'">
                <p v-if="subtitle" class="-mt-2 mb-6 text-sm text-gray-500">{{ subtitle }}</p>
                <slot />
            </main>
        </div>

        <!-- Mobile: bottom tab bar + hamburger drawer -->
        <MobileTabBar :items="bottom" :more-label="$t('nav.more')" :more-open="drawer" @more="drawer = true" />

        <Drawer :show="drawer" @close="drawer = false">
            <template #header><Logo /></template>
            <div class="space-y-6 p-4">
                <div v-for="g in groups" :key="g.title">
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ g.title }}</p>
                    <select v-if="g.switcher" class="input mb-2 py-2 text-sm" :value="user.business?.id" :aria-label="$t('nav.switch_business')" @change="switchBusiness($event.target.value)">
                        <option v-for="b in user.businesses" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <Link v-for="item in g.items" :key="item.href" :href="item.href" class="flex items-center gap-3 rounded-2xl px-3 py-3 text-[15px]" :class="isActive(item.match, item.href) ? 'bg-navy-950 text-white' : 'hover:bg-navy-50'" @click="drawer = false">
                        <Icon :name="item.icon" :size="18" />{{ item.label }}
                    </Link>
                </div>
                <div class="border-t border-[var(--border)] pt-4">
                    <Link :href="route('settings.profile')" class="flex items-center gap-3 rounded-2xl px-3 py-3 hover:bg-navy-50"><Icon name="settings" :size="18" />{{ $t('nav.settings') }}</Link>
                    <Link :href="route('support.index')" class="flex items-center gap-3 rounded-2xl px-3 py-3 hover:bg-navy-50"><Icon name="info" :size="18" />{{ $t('nav.support') }}</Link>
                    <LocaleSwitch />
                    <button type="button" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-rose-600 hover:bg-rose-50" @click="logout"><Icon name="logout" :size="18" />{{ $t('nav.logout') }}</button>
                </div>
            </div>
        </Drawer>
        <FlashToast />
    </div>
</template>
