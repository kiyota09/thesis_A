<script setup>
import { usePage, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import {
    LayoutDashboard, BarChart3, Package, LogOut, ChevronRight, CreditCard,
    UserPlus, Spool, ClipboardList, ChartNoAxesCombined, ShoppingBasket,
    HandCoins, FileUser, DoorOpen, BicepsFlexed, Truck, Wallet, Factory, Book,
    Boxes, ShoppingCart, Warehouse, Globe, Clock, CalendarDays, History, Users,
    UserPen, Settings, Receipt, HelpCircle, ShieldCheck, Building2, RefreshCw,
    ClipboardCheck, FileText, Send, ShoppingBag, User, TrendingUp, XCircle, Eye,
    Award, Archive, CalendarCheck, UserX, AlertCircle, UserCog, MessageSquare,
    Navigation, MapPin, Briefcase, Plus, ArrowLeft, Paperclip, Loader2, Info,
    Phone, Mail, Calendar, Tag, Weight, Ruler, Layers, ArrowRight, Zap, Activity,
    DollarSign, Users as UsersIcon, UserCog2, Camera, Sparkles, Palette, Wrench, CheckCircle2
} from 'lucide-vue-next'

const page = usePage()

const user = computed(() => page.props.auth.user)
const client = computed(() => page.props.auth.client)
const supplier = computed(() => page.props.auth.supplier || (page.props.auth.user?.business_name ? page.props.auth.user : null))
const currentUrl = computed(() => page.url)

// ─── PERSISTENCE HELPERS ───────────────────────────────────────────────────────
const STORAGE_PREFIX = 'sidebar_'

const getStored = (key, fallback = false) => {
    try {
        const raw = sessionStorage.getItem(STORAGE_PREFIX + key)
        return raw !== null ? JSON.parse(raw) : fallback
    } catch {
        return fallback
    }
}

const setStored = (key, value) => {
    try {
        sessionStorage.setItem(STORAGE_PREFIX + key, JSON.stringify(value))
    } catch { }
}

// ─── DROPDOWN STATES ──────────────────────────────────────────────────────────
const isWorkforceSubOpen = ref(getStored('workforce'))
const isHrmOpen = ref(getStored('hrm'))
const isCrmOpen = ref(getStored('crm'))
const isEcoOpen = ref(getStored('eco'))
const isScmOpen = ref(getStored('scm'))
const isWarehouseOpen = ref(getStored('warehouse'))
const isInventoryOpen = ref(getStored('inventory'))
const isProOpen = ref(getStored('pro'))
const isManOpen = ref(getStored('man'))
const isOrdOpen = ref(getStored('ord'))
const isLogisticsOpen = ref(getStored('logistics'))
const isQualityCheckerOpen = ref(getStored('quality_checker'))

// For dynamically created role dropdowns inside MAN
const roleDropdownStates = ref({})

const toggleRoleDropdown = (roleKey) => {
    roleDropdownStates.value[roleKey] = !roleDropdownStates.value[roleKey]
    setStored(`role_${roleKey}`, roleDropdownStates.value[roleKey])
}

const getRoleDropdownState = (roleKey, defaultState = false) => {
    if (roleDropdownStates.value[roleKey] === undefined) {
        const stored = getStored(`role_${roleKey}`, defaultState)
        roleDropdownStates.value[roleKey] = stored
    }
    return roleDropdownStates.value[roleKey]
}

const toggleWorkforceSub = () => { isWorkforceSubOpen.value = !isWorkforceSubOpen.value; setStored('workforce', isWorkforceSubOpen.value) }
const toggleHrm = () => { isHrmOpen.value = !isHrmOpen.value; setStored('hrm', isHrmOpen.value) }
const toggleCrm = () => { isCrmOpen.value = !isCrmOpen.value; setStored('crm', isCrmOpen.value) }
const toggleEco = () => { isEcoOpen.value = !isEcoOpen.value; setStored('eco', isEcoOpen.value) }
const toggleScm = () => { isScmOpen.value = !isScmOpen.value; setStored('scm', isScmOpen.value) }
const toggleWarehouse = () => { isWarehouseOpen.value = !isWarehouseOpen.value; setStored('warehouse', isWarehouseOpen.value) }
const toggleInventory = () => { isInventoryOpen.value = !isInventoryOpen.value; setStored('inventory', isInventoryOpen.value) }
const togglePro = () => { isProOpen.value = !isProOpen.value; setStored('pro', isProOpen.value) }
const toggleMan = () => { isManOpen.value = !isManOpen.value; setStored('man', isManOpen.value) }
const toggleOrd = () => { isOrdOpen.value = !isOrdOpen.value; setStored('ord', isOrdOpen.value) }
const toggleLogistics = () => { isLogisticsOpen.value = !isLogisticsOpen.value; setStored('logistics', isLogisticsOpen.value) }
const toggleQualityChecker = () => { isQualityCheckerOpen.value = !isQualityCheckerOpen.value; setStored('quality_checker', isQualityCheckerOpen.value) }

// ─── SCROLL POSITION PERSISTENCE ──────────────────────────────────────────────
const sidebarScrollRef = ref(null)

const onSidebarScroll = () => {
    if (sidebarScrollRef.value) {
        setStored('scrollTop', sidebarScrollRef.value.scrollTop)
    }
}

onMounted(() => {
    if (sidebarScrollRef.value) {
        const savedScrollTop = getStored('scrollTop', 0)
        sidebarScrollRef.value.scrollTop = savedScrollTop
        sidebarScrollRef.value.addEventListener('scroll', onSidebarScroll, { passive: true })
    }
})

onBeforeUnmount(() => {
    if (sidebarScrollRef.value) {
        sidebarScrollRef.value.removeEventListener('scroll', onSidebarScroll)
    }
})

// ─── AUTH & PERMISSIONS ───────────────────────────────────────────────────────
const showLogoutModal = ref(false)

const userModuleAccess = computed(() => {
    if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
        return page.props.auth.user?.granted_modules || []
    }
    return []
})

const grantedModules = computed(() => {
    if (user.value?.is_manufacturing_supervisor) {
        return page.props.auth.user?.granted_modules || []
    }
    return []
})

const canAccessModule = (moduleName) => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
        return userModuleAccess.value.includes(moduleName)
    }
    if (user.value?.is_manufacturing_supervisor) {
        if (moduleName === 'MAN') return true
        return grantedModules.value.includes(moduleName)
    }
    if (moduleName === 'MAN' && user.value?.manufacturing_role) {
        return true
    }
    return user.value?.role === moduleName
}

const canAccessWorkforce = () => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
        return userModuleAccess.value.includes('WRF')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('WRF')
    }
    const perms = user.value?.workforce_permissions
    return perms && perms.length > 0
}

const hasWorkforcePermission = (pageName) => {
    if (user.value?.role === 'CEO') return true
    const perms = user.value?.workforce_permissions
    if (!perms) return false
    return perms.includes(pageName)
}

const hasPagePermission = (moduleKey, pageKey) => {
    if (user.value?.role === 'CEO') return true
    const perms = user.value?.page_permissions || page.props.auth?.page_permissions || []
    return perms.some(p => p.module === moduleKey && p.page === pageKey)
}

const hasHrmPermission = (pageKey) => hasPagePermission('HRM', pageKey)
const hasCrmPermission = (pageKey) => hasPagePermission('CRM', pageKey)

const hasModulePermission = (moduleKey, permissionKey) => {
    if (user.value?.role === 'CEO') return true
    const modulePerms = user.value?.permissions?.[moduleKey]
    return modulePerms ? modulePerms.includes(permissionKey) : false
}

const hasWarehouseAccess = computed(() => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
        return canAccessModule('WAR')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('WAR')
    }
    return user.value?.has_warehouse_access === true
})

const hasInventoryAccess = computed(() => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
        return canAccessModule('INV')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('INV')
    }
    return user.value?.has_inventory_access === true
})

const hasOrdAccess = computed(() => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
        return canAccessModule('ORD')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('ORD')
    }
    return user.value?.has_ord_access === true
})

const hasLogisticsAccess = computed(() => {
    if (user.value?.role === 'CEO') return true
    if (user.value?.position === 'secretary' || user.value?.position === 'general_manager') {
        return canAccessModule('LOG')
    }
    if (user.value?.is_manufacturing_supervisor) {
        return grantedModules.value.includes('LOG')
    }
    if (user.value?.role === 'LOG' && user.value?.position === 'manager') return true
    return user.value?.logistics_access === true
})

const isDriver = computed(() => user.value?.driver !== null || user.value?.log_role === 'driver')
const isConductor = computed(() => user.value?.conductor !== null || user.value?.log_role === 'conductor')
const isEmployeePortal = computed(() => currentUrl.value.startsWith('/dashboard/employee-ui'))
const isClient = computed(() => !!client.value)
const isSupplier = computed(() => !!supplier.value || currentUrl.value.startsWith('/supplier'))

const isManufacturingSupervisor = computed(() => user.value?.is_manufacturing_supervisor === true)
const supervisorRoles = computed(() => user.value?.supervisor_roles || [])
const activeManufacturingRole = computed(() => user.value?.active_manufacturing_role || null)

const switchManufacturingRole = (role) => {
    router.post(route('man.supervisor.switch'), { role }, {
        preserveScroll: true,
        onSuccess: () => window.location.reload()
    })
}

const formatRoleLabel = (role) => {
    if (!role) return ''
    return role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const supervisedDepartment = computed(() => {
    if (!isManufacturingSupervisor.value) return null
    return user.value?.supervisor_department || null
})

// Helper to generate three links for a role (as an array of link objects)
const getRoleLinks = (roleWithUnderscores, label, icon, hasReports = true) => {
    const roleWithHyphens = roleWithUnderscores.replace(/_/g, '-')
    const routePrefix = `man.staff.${roleWithHyphens}`
    const links = [
        { label: 'Dashboard', href: route(`${routePrefix}.dashboard`), icon: LayoutDashboard },
        { label: label, href: route(`${routePrefix}.page`), icon: icon }
    ]
    if (hasReports) {
        links.push({ label: 'Reports', href: route(`${routePrefix}.reports`), icon: FileText })
    }
    return links
}

// Helper to create a dropdown item for a role (used inside MAN module)
const createRoleDropdown = (roleKey, roleLabel, roleIcon, hasReports = true) => {
    const isOpen = getRoleDropdownState(roleKey, false)
    return {
        label: roleLabel,
        icon: roleIcon,
        isDropdown: true,
        isOpen: isOpen,
        toggle: () => toggleRoleDropdown(roleKey),
        children: getRoleLinks(roleKey, roleLabel, roleIcon, hasReports)
    }
}

// Department staff roles for supervisor view (returns array of dropdown items)
const departmentStaffRoles = computed(() => {
    const dept = supervisedDepartment.value
    if (dept === 'knitting') {
        return [createRoleDropdown('knitting_yarn', 'Knitting Yarn', Sparkles, true)]
    } else if (dept === 'dyeing') {
        return [
            createRoleDropdown('dyeing_color', 'Dyeing Color', Palette, true),
            createRoleDropdown('dyeing_fabric_softener', 'Dyeing Fabric Softener', Palette, true),
            createRoleDropdown('dyeing_squeezer', 'Dyeing Squeezer', Palette, true),
            createRoleDropdown('dyeing_ironing', 'Dyeing Ironing', Palette, true),
            createRoleDropdown('dyeing_forming', 'Dyeing Forming', Palette, true),
            createRoleDropdown('dyeing_packaging', 'Dyeing Packaging', Palette, false)
        ]
    } else if (dept === 'maintenance') {
        return [createRoleDropdown('maintenance_checker', 'Maintenance Checker', Wrench, true)]
    }
    return []
})

// ─── HELPER: CHECK IF USER CAN SEE A MODULE CHILD ─────────────────────────────
const canAccessModuleChild = (moduleKey, childPermKey, moduleSpecificCheck = null) => {
    if (user.value?.role === 'CEO') return true
    if (moduleSpecificCheck) return moduleSpecificCheck(childPermKey)

    const granularPerms = user.value?.permissions?.[moduleKey]
    if (granularPerms && granularPerms.length) {
        return granularPerms.includes(childPermKey)
    }

    const isManager = user.value?.role === moduleKey && user.value?.position === 'manager'
    const isSecretaryOrGMWithModule = (user.value?.position === 'secretary' || user.value?.position === 'general_manager') &&
        userModuleAccess.value.includes(moduleKey)
    const isSupervisorWithModule = user.value?.is_manufacturing_supervisor && grantedModules.value.includes(moduleKey)
    return isManager || isSecretaryOrGMWithModule || isSupervisorWithModule
}

// ─── NAV ITEMS ────────────────────────────────────────────────────────────────
const navItems = computed(() => {
    if (isSupplier.value) {
        return [
            { label: 'Vendor Hub', href: route('supplier.dashboard'), icon: LayoutDashboard },
            { label: 'Purchase Orders', href: route('supplier.orders'), icon: ShoppingCart },
        ]
    }

    if (isClient.value) {
        return [
            { label: 'Dashboard', href: route('client.dashboard'), icon: LayoutDashboard },
            { label: 'Products', href: route('client.products'), icon: ShoppingBag },
            { label: 'Conversations', href: route('client.conversations'), icon: MessageSquare },
            { label: 'Orders', href: route('client.orders'), icon: ShoppingCart },
            { label: 'Invoices', href: route('client.invoices'), icon: Receipt },
            { label: 'Receiving', href: route('client.receiving'), icon: Truck },
            { label: 'Profile', href: route('client.profile.edit'), icon: User },
            { label: 'Support', href: route('client.support'), icon: HelpCircle },
        ]
    }

    if (isEmployeePortal.value) {
        return [
            { label: 'Employee Dashboard', href: route('employee.ui.dashboard'), icon: Clock },
            { label: 'My Attendance', href: route('employee.ui.clock'), icon: CalendarDays },
            { label: 'Leave Request', href: route('employee.ui.leave'), icon: History },
            { label: 'Payslip', href: route('employee.ui.payslip'), icon: HandCoins },
        ]
    }

    // ─── INTERNAL USER ─────────────────────────────────────────────────────────
    const items = []
    const userRole = user.value?.role?.toUpperCase()
    const userPosition = user.value?.position?.toLowerCase()
    const isCEO = user.value?.role === 'CEO'
    const isSecretaryOrGM = user.value?.position === 'secretary' || user.value?.position === 'general_manager'

    if (isCEO) {
        items.push({ label: 'CEO Dashboard', href: route('dashboard'), icon: LayoutDashboard })
        items.push({ label: 'Organization Chart', href: route('ceo.access'), icon: ShieldCheck })
        items.push({ label: 'Geolocation', href: route('ceo.location.index'), icon: MapPin })
    }

    if (userRole === 'LOG' && userPosition === 'staff') {
        if (isDriver.value) {
            items.push({ label: 'My Deliveries', href: route('logistics.driver.portal'), icon: Truck })
        } else if (isConductor.value) {
            items.push({ label: 'My Trips', href: route('logistics.conductor.portal'), icon: Navigation })
        }
        return items
    }

    if (userPosition === 'trainee') {
        items.push(
            { label: 'Time In/Out', href: route('trainee.timekeeping'), icon: Clock },
            { label: 'Attendance', href: route('trainee.attendance'), icon: CalendarDays },
            { label: 'Payslips', href: route('trainee.payslip'), icon: HandCoins }
        )
        return items
    }

    // ─── MODULE CHILDREN (HRM, CRM, MAN, LOG, etc.) ───────────────────────────
    const getFilteredHrmChildren = () => {
        const all = [
            { label: 'Dashboard', href: route('hrm.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Employees', href: route('hrm.employees.index'), icon: Users, permKey: 'employee' },
            { label: 'Applications', href: route('hrm.applications.index'), icon: FileText, permKey: 'application' },
            { label: 'Interviews', href: route('hrm.interview.index'), icon: Eye, permKey: 'interview' },
            { label: 'Trainees', href: route('hrm.trainee.index'), icon: Award, permKey: 'trainee' },
            { label: 'Onboarding', href: route('hrm.onboarding.index'), icon: UserPlus, permKey: 'onboarding' },
            { label: 'Archive', href: route('hrm.applications.rejected'), icon: Archive, permKey: 'reject' },
            { label: 'Payroll', href: route('hrm.payroll'), icon: HandCoins, permKey: 'payroll' },
            { label: 'Analytics', href: route('hrm.analytics'), icon: ChartNoAxesCombined, permKey: 'analytics' },
            { label: 'Access Control', href: route('hrm.access.index'), icon: ShieldCheck, permKey: 'access' },
        ]
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'HRM') return all
        if (isSecretaryOrGM && canAccessModule('HRM')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('HRM')) return all
        const filtered = all.filter(child => hasHrmPermission(child.permKey))
        if (filtered.length === 0) return [all[0]]
        return filtered
    }

    const getFilteredCrmChildren = () => {
        const all = [
            { label: 'Dashboard', href: route('crm.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Leads', href: route('crm.lead'), icon: FileUser, permKey: 'leads' },
            { label: 'Interviews', href: route('crm.interview.index'), icon: Eye, permKey: 'interviews' },
            { label: 'Trainees', href: route('crm.trainee.index'), icon: Award, permKey: 'trainees' },
            { label: 'Approvals', href: route('crm.approval.index'), icon: ClipboardCheck, permKey: 'approvals' },
            { label: 'Customer Profiles', href: route('crm.customerprofile.index'), icon: Users, permKey: 'customer_profiles' },
            { label: 'Investigation', href: route('crm.investigation.index'), icon: AlertCircle, permKey: 'investigation' },
            { label: 'Access Control', href: route('crm.access.index'), icon: ShieldCheck, permKey: 'access' },
        ]
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'CRM') return all
        if (isSecretaryOrGM && canAccessModule('CRM')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('CRM')) return all
        const filtered = all.filter(child => hasCrmPermission(child.permKey))
        if (filtered.length === 0) return [all[0]]
        return filtered
    }

    const getFilteredWorkforceChildren = () => {
        const all = [
            { label: 'Dashboard', href: route('workforce.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Scheduler', href: route('workforce.scheduler'), icon: CalendarCheck, permKey: 'scheduler' },
            { label: 'Leave Requests', href: route('workforce.leave'), icon: FileText, permKey: 'leave' },
            { label: 'Absence Tracking', href: route('workforce.absent'), icon: UserX, permKey: 'absent' },
        ]
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'WRF') return all
        if (isSecretaryOrGM && canAccessModule('WRF')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('WRF')) return all
        return all.filter(child => hasWorkforcePermission(child.permKey))
    }

    const getFilteredManChildren = () => {
        // Quality Checker dropdown items using direct URLs (to avoid Ziggy errors)
        const qualityCheckerChildren = [
            { label: 'Dashboard', href: '/dashboard/man/checker-quality', icon: LayoutDashboard },
            { label: 'Production', href: '/dashboard/man/checker-quality/production', icon: ClipboardList },
        ]

        // Manager-level pages
        const managerChildren = [
            { label: 'Dashboard', href: route('man.manager.dashboard'), icon: Factory, permKey: 'dashboard' },
            { label: 'Production Orders', href: route('man.manager.production'), icon: ClipboardList, permKey: 'production' },
            { label: 'Rejected Items', href: route('man.manager.rejected'), icon: XCircle, permKey: 'reject' },
            // NEW: Production Inventory link
            { label: 'Production Inventory', href: route('man.inventory.index'), icon: Boxes, permKey: 'inventory' },
            // Quality Checker as dropdown
            {
                label: 'Quality Checker',
                icon: CheckCircle2,
                isDropdown: true,
                isOpen: isQualityCheckerOpen.value,
                toggle: toggleQualityChecker,
                children: qualityCheckerChildren
            },
            { label: 'Interviews', href: route('man.interview.index'), icon: Eye, permKey: 'interview' },
            { label: 'Trainees', href: route('man.trainee.index'), icon: Award, permKey: 'trainee' },
        ]

        const isManufacturingManager = userPosition === 'manager' && user.value?.role === 'MAN'
        if (isCEO || isSecretaryOrGM || isManufacturingManager) {
            managerChildren.push({ label: 'Access Control', href: route('man.access.manage'), icon: ShieldCheck, permKey: 'access' })
        }

        let children = []

        // Case 1: User has manager-level access (CEO, MAN manager, secretary/GM, or supervisor)
        if (isCEO || (userPosition === 'manager' && user.value?.role === 'MAN') || isSecretaryOrGM || isManufacturingSupervisor.value) {
            children = [...managerChildren]

            // For supervisors, also add department staff roles as dropdowns
            if (isManufacturingSupervisor.value && supervisedDepartment.value) {
                const staffDropdowns = departmentStaffRoles.value
                if (staffDropdowns.length) {
                    children.push({ isDivider: true, label: '── Department Staff ──' })
                    children.push(...staffDropdowns)
                }
            }
        } else {
            // Case 2: Regular staff with a single manufacturing_role
            const manufacturingRole = user.value?.manufacturing_role
            const staffRoleConfig = {
                knitting_yarn:          { label: 'Knitting Yarn',          icon: Sparkles, hasReports: true },
                dyeing_color:           { label: 'Dyeing Color',           icon: Palette,  hasReports: true },
                dyeing_fabric_softener: { label: 'Dyeing Fabric Softener', icon: Palette,  hasReports: true },
                dyeing_squeezer:        { label: 'Dyeing Squeezer',        icon: Palette,  hasReports: true },
                dyeing_ironing:         { label: 'Dyeing Ironing',         icon: Palette,  hasReports: true },
                dyeing_forming:         { label: 'Dyeing Forming',         icon: Palette,  hasReports: true },
                dyeing_packaging:       { label: 'Dyeing Packaging',       icon: Palette,  hasReports: false },
                maintenance_checker:    { label: 'Maintenance Checker',    icon: Wrench,   hasReports: true },
            }
            const config = staffRoleConfig[manufacturingRole]
            if (config) {
                children = [createRoleDropdown(manufacturingRole, config.label, config.icon, config.hasReports)]
            }
        }

        return children
    }

    const getFilteredLogisticsChildren = () => {
        const all = [
            { label: 'Dashboard', href: route('logistics.dashboard'), icon: LayoutDashboard },
            { label: 'Load', href: route('logistics.load.index'), icon: Package },
            { label: 'Dispatch', href: route('logistics.dispatch.index'), icon: Send },
            { label: 'Fleet', href: route('logistics.fleet.index'), icon: Truck },
            { label: 'Drivers', href: route('logistics.drivers.index'), icon: Users },
            { label: 'Interviews', href: route('logistics.interview.index'), icon: Eye },
            { label: 'Trainees', href: route('logistics.trainee.index'), icon: Award },
            { label: 'Routes', href: route('logistics.routes'), icon: Navigation },
            { label: 'Proof', href: route('logistics.proof.index'), icon: Camera },
            { label: 'Reports', href: route('logistics.reports.index'), icon: FileText },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('logistics.access.index'), icon: ShieldCheck })
        }
        return all
    }

    const getFilteredEcoChildren = () => {
        const all = [
            { label: 'Dashboard', href: route('eco.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Store', href: route('eco.store'), icon: ShoppingBag, permKey: 'store' },
            { label: 'Inquiries', href: route('eco.inquiries'), icon: MessageSquare, permKey: 'inquiry' },
            { label: 'Suppliers', href: route('eco.suppliers'), icon: UsersIcon, permKey: 'supplier' },
            { label: 'Credit', href: route('eco.credit'), icon: CreditCard, permKey: 'credit' },
            { label: 'Push Center', href: route('eco.push'), icon: Send, permKey: 'push' },
            { label: 'Access Control', href: route('eco.access'), icon: ShieldCheck, permKey: 'access' },
        ]
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'ECO') return all
        if (isSecretaryOrGM && canAccessModule('ECO')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('ECO')) return all
        return all.filter(child => hasModulePermission('ECO', child.permKey))
    }

    const getFilteredOrdChildren = () => {
        const all = [
            { label: 'Orders', href: route('ord.orders'), icon: ClipboardList, permKey: 'orders' },
            { label: 'Productions', href: route('ord.productions'), icon: Factory, permKey: 'productions' },
            { label: 'Delivery', href: route('ord.delivery'), icon: Truck, permKey: 'delivery' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('ord.ceo-access.index'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'ORD') return all
        if (isSecretaryOrGM && canAccessModule('ORD')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('ORD')) return all
        return all.filter(child => hasModulePermission('ORD', child.permKey))
    }

    const getFilteredScmChildren = () => {
        const all = [
            { label: 'Sales Orders', href: route('scm.sales-orders'), icon: ShoppingCart, permKey: 'sales' },
            { label: 'Procurement Orders', href: route('scm.procurement-orders'), icon: ClipboardList, permKey: 'procurement' },
            { label: 'Vendors', href: route('scm.vendors'), icon: Building2, permKey: 'vendor' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('scm.access.index'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'SCM') return all
        if (isSecretaryOrGM && canAccessModule('SCM')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('SCM')) return all
        return all.filter(child => hasModulePermission('SCM', child.permKey))
    }

    const getFilteredWarehouseChildren = () => {
        const all = [
            { label: 'All Warehouses', href: route('warehouse.index'), icon: Warehouse, permKey: 'warehouse' },
            // { label: 'Monitor', href: route('warehouse.monitor', { warehouse: 'placeholder' }), icon: Eye, permKey: 'monitor' },
            { label: 'Receiving', href: route('warehouse.receiving'), icon: Truck, permKey: 'receiving' },
            { label: 'Packages', href: route('warehouse.packages'), icon: Package, permKey: 'packages' },
            { label: 'Rejects', href: route('warehouse.rejects'), icon: XCircle, permKey: 'reject' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('warehouse.access'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'WAR') return all
        if (isSecretaryOrGM && canAccessModule('WAR')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('WAR')) return all
        return all.filter(child => hasModulePermission('WAR', child.permKey))
    }

    const getFilteredInventoryChildren = () => {
        const all = [
            { label: 'Dashboard', href: route('inv.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Materials', href: route('inv.materials'), icon: Spool, permKey: 'materials' },
            { label: 'Products', href: route('inv.products'), icon: Package, permKey: 'products' },
            { label: 'Recipes', href: route('inv.bom'), icon: Layers, permKey: 'bom' },
            { label: 'Stock Checker', href: route('inv.checker'), icon: AlertCircle, permKey: 'checker' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('inv.access'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'INV') return all
        if (isSecretaryOrGM && canAccessModule('INV')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('INV')) return all
        return all.filter(child => hasModulePermission('INV', child.permKey))
    }

    const getFilteredProChildren = () => {
        const all = [
            { label: 'Dashboard', href: route('pro.manager.dashboard'), icon: LayoutDashboard, permKey: 'dashboard' },
            { label: 'Quotations', href: route('pro.manager.supplier-quotations'), icon: FileText, permKey: 'quotations' },
            { label: 'Receipts', href: route('pro.manager.receipt'), icon: Send, permKey: 'receipt' },
        ]
        if (isCEO) {
            all.push({ label: 'Access Control', href: route('pro.access.index'), icon: ShieldCheck, permKey: 'access' })
        }
        if (isCEO) return all
        if (userPosition === 'manager' && user.value?.role === 'PRO') return all
        if (isSecretaryOrGM && canAccessModule('PRO')) return all
        if (user.value?.is_manufacturing_supervisor && grantedModules.value.includes('PRO')) return all
        return all.filter(child => hasModulePermission('PRO', child.permKey))
    }

    // ─── COLLECT MODULES ──────────────────────────────────────────────────────
    const coreModules = []
    const featureModules = []

    const addModule = (moduleKey, label, icon, childrenGetter, isOpenRef, toggleFn, condition) => {
        if (!condition) return
        const children = childrenGetter()
        if (children.length === 0) return
        const moduleItem = { label, icon, isDropdown: true, isOpen: isOpenRef.value, toggle: toggleFn, children }
        if (['HRM', 'CRM', 'MAN', 'LOG'].includes(moduleKey)) {
            coreModules.push(moduleItem)
        } else {
            featureModules.push(moduleItem)
        }
    }

    addModule('HRM', 'Human Resource', Users, getFilteredHrmChildren, isHrmOpen, toggleHrm, canAccessModule('HRM'))
    addModule('CRM', 'Customer Relationship', UserPen, getFilteredCrmChildren, isCrmOpen, toggleCrm, canAccessModule('CRM'))
    addModule('MAN', 'Manufacturing', Factory, getFilteredManChildren, isManOpen, toggleMan, canAccessModule('MAN'))
    addModule('LOG', 'Logistics', Truck, getFilteredLogisticsChildren, isLogisticsOpen, toggleLogistics, hasLogisticsAccess.value)
    addModule('ECO', 'E-Commerce', ShoppingBag, getFilteredEcoChildren, isEcoOpen, toggleEco, canAccessModule('ECO'))
    addModule('ORD', 'Order Management', ClipboardCheck, getFilteredOrdChildren, isOrdOpen, toggleOrd, hasOrdAccess.value)
    addModule('SCM', 'Supply Chain', Truck, getFilteredScmChildren, isScmOpen, toggleScm, canAccessModule('SCM'))
    addModule('WAR', 'Warehouse', Warehouse, getFilteredWarehouseChildren, isWarehouseOpen, toggleWarehouse, hasWarehouseAccess.value)
    addModule('INV', 'Inventory', Boxes, getFilteredInventoryChildren, isInventoryOpen, toggleInventory, hasInventoryAccess.value)
    addModule('PRO', 'Procurement', ShoppingCart, getFilteredProChildren, isProOpen, togglePro, canAccessModule('PRO'))

    const filteredWorkforceChildren = getFilteredWorkforceChildren()
    if (canAccessWorkforce() && filteredWorkforceChildren.length > 0) {
        featureModules.push({
            label: 'Workforce Management',
            icon: CalendarDays,
            isDropdown: true,
            isOpen: isWorkforceSubOpen.value,
            toggle: toggleWorkforceSub,
            children: filteredWorkforceChildren
        })
    }

    if (coreModules.length > 0) {
        items.push({ isHeading: true, label: 'Core Modules' })
        items.push(...coreModules)
    }
    if (featureModules.length > 0) {
        items.push({ isHeading: true, label: 'Feature Modules' })
        items.push(...featureModules)
    }

    return items
})

// ─── HELPERS ──────────────────────────────────────────────────────────────────
const isActive = (href) => {
    if (href === '#') return false
    return currentUrl.value === href || currentUrl.value.startsWith(href + '/')
}

const displayName = computed(() => {
    if (isSupplier.value) return supplier.value?.representative_name
    if (isClient.value) return client.value?.company_name
    return user.value?.name
})
const displayInitial = computed(() => displayName.value?.charAt(0) ?? '?')
const userPhotoUrl = computed(() => {
    if (user.value?.profile_photo_path) return `/storage/${user.value.profile_photo_path}`
    if (supplier.value?.profile_photo_path) return `/storage/${supplier.value.profile_photo_path}`
    if (client.value?.profile_photo_path) return `/storage/${client.value.profile_photo_path}`
    return null
})
const displayDepartment = computed(() => isSupplier.value ? 'Supplier' : (isClient.value ? client.value?.business_type : user.value?.role))
const displayPosition = computed(() => {
    if (isSupplier.value) return supplier.value?.business_name ?? 'Vendor'
    if (isClient.value) return 'Partner'
    if (user.value?.is_manufacturing_supervisor) return 'Supervisor'
    return user.value?.position
})
const sidebarLabel = computed(() => isSupplier.value ? 'Vendor' : (isClient.value ? 'Partner' : (isEmployeePortal.value ? 'Employee' : 'System')))
const logoutRoute = computed(() => isClient.value ? route('client.logout') : (isSupplier.value ? route('supplier.logout') : route('logout')))
</script>

<template>
    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 z-40 transition-all duration-300 h-screen">
        <div
            class="flex flex-col h-full bg-white/70 dark:bg-gray-950/70 backdrop-blur-xl border-r border-gray-200/40 dark:border-gray-800/40 shadow-2xl">

            <div class="flex items-center h-20 flex-shrink-0 px-4 pt-2">
                <div class="relative w-full">
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-indigo-500/10 dark:from-blue-400/10 dark:to-indigo-400/10 rounded-2xl blur-xl">
                    </div>
                    <div
                        class="relative flex items-center gap-2.5 p-2 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-2xl shadow-sm border border-gray-100/50 dark:border-gray-800/50 w-full">
                        <div :class="isSupplier ? 'bg-emerald-600 shadow-emerald-500/30' : 'bg-blue-600 shadow-blue-500/30'"
                            class="h-9 w-9 flex-shrink-0 rounded-xl flex items-center justify-center shadow-lg">
                            <img src="/images/applogo.png" alt="Logo"
                                class="h-6 w-6 object-contain brightness-0 invert" />
                        </div>
                        <div class="flex flex-col overflow-hidden">
                            <h2
                                class="text-[13px] font-black text-gray-900 dark:text-white leading-tight tracking-tight uppercase">
                                Monti <span :class="isSupplier ? 'text-emerald-600' : 'text-blue-600'">Textile</span>
                            </h2>
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest truncate">{{
                                sidebarLabel }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div ref="sidebarScrollRef" class="flex-1 overflow-y-auto px-3 py-4 custom-scrollbar">
                <nav class="space-y-1">
                    <template v-for="item in navItems" :key="item.label || item.isHeading">
                        <!-- Heading -->
                        <div v-if="item.isHeading" class="mb-3 px-2 pt-4 first:pt-0">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.15em]">{{ item.label }}</p>
                        </div>

                        <!-- Dropdown (level 1) -->
                        <div v-else-if="item.isDropdown" class="space-y-1">
                            <button @click="item.toggle"
                                :class="[item.isOpen ? 'text-blue-600 bg-white/60 dark:bg-gray-900/60 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:bg-white/40 dark:hover:bg-gray-900/40']"
                                class="group w-full flex items-center justify-between px-3 py-2.5 text-[13px] font-bold rounded-xl transition-all duration-300 backdrop-blur-sm">
                                <div class="flex items-center">
                                    <div :class="[item.isOpen ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600' : 'text-gray-400']"
                                        class="p-1.5 rounded-lg mr-2.5 transition-colors duration-300">
                                        <component :is="item.icon" class="h-4.5 w-4.5" />
                                    </div>
                                    <span class="truncate tracking-tight">{{ item.label }}</span>
                                </div>
                                <ChevronRight
                                    :class="['h-3.5 w-3.5 transition-transform duration-300', item.isOpen ? 'rotate-90' : 'text-gray-400']" />
                            </button>

                            <!-- Children (may contain further dropdowns or links) -->
                            <div v-show="item.isOpen" class="pl-6 space-y-1 mt-1 transition-all">
                                <template v-for="subItem in item.children" :key="subItem.label">
                                    <!-- Sub-dropdown (level 2) -->
                                    <div v-if="subItem.isDropdown" class="space-y-1">
                                        <button @click="subItem.toggle"
                                            :class="[subItem.isOpen ? 'text-blue-600 bg-white/60 dark:bg-gray-900/60 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:bg-white/40 dark:hover:bg-gray-900/40']"
                                            class="group w-full flex items-center justify-between px-3 py-2 text-[12px] font-bold rounded-xl transition-all duration-300 backdrop-blur-sm">
                                            <div class="flex items-center">
                                                <div :class="[subItem.isOpen ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600' : 'text-gray-400']"
                                                    class="p-1 rounded-lg mr-2 transition-colors duration-300">
                                                    <component :is="subItem.icon" class="h-3.5 w-3.5" />
                                                </div>
                                                <span class="truncate tracking-tight">{{ subItem.label }}</span>
                                            </div>
                                            <ChevronRight
                                                :class="['h-3 w-3 transition-transform duration-300', subItem.isOpen ? 'rotate-90' : 'text-gray-400']" />
                                        </button>
                                        <div v-show="subItem.isOpen" class="pl-8 space-y-1">
                                            <Link v-for="link in subItem.children" :key="link.label"
                                                :href="link.href" preserve-scroll preserve-state
                                                :class="[isActive(link.href) ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white']"
                                                class="flex items-center py-1.5 text-[11px] font-medium transition-colors">
                                                <component :is="link.icon" class="h-3 w-3 mr-2" />
                                                {{ link.label }}
                                            </Link>
                                        </div>
                                    </div>
                                    <!-- Divider -->
                                    <div v-else-if="subItem.isDivider" class="text-[10px] text-gray-400 py-1 px-2">{{ subItem.label }}</div>
                                    <!-- Direct link (level 2) -->
                                    <Link v-else :href="subItem.href" preserve-scroll preserve-state
                                        :class="[isActive(subItem.href) ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900 dark:hover:text-white']"
                                        class="flex items-center py-2 text-[12px] font-bold transition-colors">
                                        <component :is="subItem.icon" class="h-3.5 w-3.5 mr-2.5" />
                                        {{ subItem.label }}
                                    </Link>
                                </template>
                            </div>
                        </div>

                        <!-- Direct link (non‑dropdown) -->
                        <Link v-else :href="item.href" preserve-scroll preserve-state
                            :class="[isActive(item.href) ? (isSupplier ? 'bg-emerald-50/80 dark:bg-emerald-900/30 text-emerald-600 shadow-sm ring-1 ring-emerald-500/20' : 'bg-blue-50/80 dark:bg-blue-900/30 text-blue-600 shadow-sm ring-1 ring-blue-500/20') : 'text-gray-500 dark:text-gray-400 hover:bg-white/40 dark:hover:bg-gray-900/40 hover:text-gray-900 dark:hover:text-white']"
                            class="group relative flex items-center justify-between px-3 py-2.5 text-[13px] font-bold rounded-xl transition-all duration-300 backdrop-blur-sm">
                            <div v-if="isActive(item.href)" :class="isSupplier ? 'bg-emerald-500' : 'bg-blue-500'"
                                class="absolute left-0 top-1/4 bottom-1/4 w-0.5 rounded-r-full"></div>
                            <div class="flex items-center relative z-10">
                                <div :class="[isActive(item.href) ? (isSupplier ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600' : 'bg-blue-100 dark:bg-blue-900/50 text-blue-600') : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300']"
                                    class="p-1.5 rounded-lg transition-colors duration-300 mr-2.5">
                                    <component :is="item.icon" class="h-4.5 w-4.5 flex-shrink-0" />
                                </div>
                                <span class="truncate tracking-tight">{{ item.label }}</span>
                            </div>
                            <ChevronRight v-if="isActive(item.href)"
                                :class="isSupplier ? 'text-emerald-600/40' : 'text-blue-600/40'" class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                </nav>
            </div>

            <div class="p-3 mt-auto flex-shrink-0 border-t border-gray-100/20 dark:border-gray-800/50">
                <div class="relative group">
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-indigo-500/10 dark:from-blue-400/10 dark:to-indigo-400/10 rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                    </div>
                    <div
                        class="relative bg-white/60 dark:bg-gray-900/60 backdrop-blur-md rounded-2xl p-2.5 border border-gray-100/50 dark:border-gray-800/50 shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="flex items-center gap-2.5">
                            <div class="relative">
                                <img v-if="userPhotoUrl" :src="userPhotoUrl"
                                    class="h-9 w-9 rounded-xl object-cover shadow-lg"
                                    :class="isSupplier ? 'shadow-emerald-500/30' : 'shadow-blue-500/30'" />
                                <div v-else
                                    :class="isSupplier ? 'from-emerald-600 to-teal-700 shadow-emerald-500/30' : 'from-blue-600 to-indigo-700 shadow-blue-500/30'"
                                    class="h-9 w-9 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-xs font-black shadow-lg uppercase">
                                    {{ displayInitial }}</div>
                                <div
                                    class="absolute -bottom-0.5 -right-0.5 h-3 w-3 bg-green-500 border-2 border-white dark:border-gray-900 rounded-full">
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-[11px] font-black text-gray-900 dark:text-white truncate uppercase tracking-tighter">
                                    {{ displayName }}</p>
                                <div class="flex items-center gap-1 mb-0.5">
                                    <Building2 class="h-2.5 w-2.5 text-gray-400" />
                                    <span :class="isSupplier ? 'text-emerald-600' : 'text-blue-600'"
                                        class="text-[8px] font-black uppercase truncate">{{ displayDepartment }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <ShieldCheck :class="isSupplier ? 'text-emerald-500' : 'text-blue-500'"
                                        class="h-2.5 w-2.5" />
                                    <span class="text-[8px] font-black text-gray-400 uppercase truncate">{{
                                        displayPosition }}</span>
                                </div>
                            </div>
                            <button @click="showLogoutModal = true"
                                class="p-2 rounded-xl bg-gray-100/80 dark:bg-gray-800/80 text-gray-400 hover:text-red-500 hover:bg-red-50/80 dark:hover:bg-red-900/20 transition-all duration-300 backdrop-blur-sm">
                                <LogOut class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <div v-if="isManufacturingSupervisor && supervisorRoles.length > 0"
                            class="mt-3 pt-3 border-t border-gray-200/50 dark:border-gray-700/50">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                <UserCog2 class="w-3 h-3" /> SWITCH ROLE
                            </p>
                            <div class="space-y-1">
                                <button v-for="role in supervisorRoles" :key="role.manufacturing_role"
                                    @click="switchManufacturingRole(role.manufacturing_role)"
                                    :class="[
                                        activeManufacturingRole === role.manufacturing_role
                                            ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-1 ring-blue-500/30'
                                            : 'bg-gray-100/70 dark:bg-gray-800/70 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20'
                                    ]"
                                    class="w-full text-left text-[11px] font-medium px-2 py-1.5 rounded-lg transition-all">
                                    {{ formatRoleLabel(role.manufacturing_role) }}
                                    <span v-if="activeManufacturingRole === role.manufacturing_role"
                                        class="float-right text-blue-500">✓</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <transition name="modal-fade">
                <div v-if="showLogoutModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="showLogoutModal = false">
                    <div
                        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 w-full max-w-sm p-6 flex flex-col items-center text-center transform transition-all duration-300 scale-100">
                        <div
                            class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                            <LogOut class="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Sign Out</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 px-2">Are you sure you want to sign out
                            of your account?</p>
                        <div class="flex gap-3 w-full">
                            <button @click="showLogoutModal = false"
                                class="flex-1 py-3 text-sm font-bold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">Cancel</button>
                            <Link :href="logoutRoute" method="post" as="button"
                                class="flex-1 py-3 text-sm font-bold rounded-xl bg-red-600 text-white hover:bg-red-700 transition shadow-lg shadow-red-500/20">
                                Confirm Sign Out</Link>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.4);
}
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
.modal-fade-enter-active .bg-white,
.modal-fade-leave-active .bg-white {
    transition: transform 0.2s ease;
}
.modal-fade-enter-from .bg-white,
.modal-fade-leave-to .bg-white {
    transform: scale(0.95) translateY(10px);
}
</style>