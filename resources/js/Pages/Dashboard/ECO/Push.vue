<template>

    <Head title="Push Center · ECO" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#F8FAFC] text-slate-900 font-sans pb-20">
            <div class="max-w-[1600px] mx-auto space-y-6 sm:space-y-8 p-4 sm:p-6 lg:p-10">

                <!-- ── Header ──────────────────────────────────────────────── -->
                <div
                    class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 bg-white rounded-[2rem] p-6 sm:p-8 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-5">
                        <div
                            class="h-14 w-14 rounded-2xl bg-black flex items-center justify-center text-white shadow-lg shadow-black/10 flex-shrink-0">
                            <Send class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600 mb-1">Dispatch
                                Unit</p>
                            <h1 class="text-3xl sm:text-4xl font-black text-black tracking-tight">
                                Push <span class="text-slate-400 font-light">Center</span>
                            </h1>
                        </div>
                    </div>

                    <div class="flex-1 max-w-2xl flex items-center gap-3 w-full">
                        <div class="relative group flex-1">
                            <Search
                                class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-300 group-focus-within:text-blue-600 transition-colors" />
                            <input v-model="searchQuery" type="text" placeholder="Search JO#, PO#, Client Name..."
                                class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-medium focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder:text-slate-400" />
                        </div>
                        <button @click="refreshData"
                            class="p-4 rounded-2xl bg-white border border-slate-100 text-slate-400 hover:text-black hover:border-black transition-all flex-shrink-0 shadow-sm hover:shadow-md active:scale-95">
                            <RefreshCw class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- ── Tabs ─────────────────────────────────────────────────── -->
                <div class="flex items-center gap-4 border-b border-slate-200 overflow-x-auto hide-scrollbar">
                    <button @click="activeTab = 'pending'"
                        :class="activeTab === 'pending' ? 'border-black text-black' : 'border-transparent text-slate-400 hover:text-slate-700'"
                        class="py-4 px-2 font-black uppercase text-xs sm:text-sm border-b-2 transition-all whitespace-nowrap flex items-center gap-2">
                        Pending Push
                        <span
                            :class="activeTab === 'pending' ? 'bg-yellow-400 text-black' : 'bg-slate-100 text-slate-500'"
                            class="px-2 py-0.5 rounded-md text-[10px]">
                            {{ filteredPendingOrders.length }}
                        </span>
                    </button>
                    <button @click="activeTab = 'pushed'"
                        :class="activeTab === 'pushed' ? 'border-black text-black' : 'border-transparent text-slate-400 hover:text-slate-700'"
                        class="py-4 px-2 font-black uppercase text-xs sm:text-sm border-b-2 transition-all whitespace-nowrap flex items-center gap-2">
                        Already Pushed
                        <span :class="activeTab === 'pushed' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500'"
                            class="px-2 py-0.5 rounded-md text-[10px]">
                            {{ filteredPushedOrders.length }}
                        </span>
                    </button>
                </div>

                <!-- ══════════════════════════════════════════════════════════
                     PENDING TAB
                     ══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'pending'" class="animate-in">

                    <!-- Mobile cards -->
                    <div class="md:hidden space-y-4">
                        <div v-for="jo in filteredPendingOrders" :key="jo.id"
                            class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col gap-5">
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-yellow-400"></div>

                            <div class="pl-2 flex justify-between items-start">
                                <div>
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">
                                        Client Name</p>
                                    <h3 class="text-base font-black text-black leading-tight">
                                        {{ jo.client?.company_name }}
                                    </h3>
                                </div>
                                <div class="text-right flex-shrink-0 ml-4">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">
                                        Volume</p>
                                    <span
                                        class="text-sm font-black text-black bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">{{
                                            jo.quantity }} kg</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pl-2">
                                <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-blue-400 mb-1">JO
                                        Number</p>
                                    <p class="text-xs font-bold text-blue-600 uppercase">{{ jo.jo_number }}</p>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">PO
                                        Number</p>
                                    <p class="text-[10px] font-mono font-bold text-slate-700">{{ jo.purchase_order_id }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-2">
                                <div>
                                    <p class="text-[9px] font-black uppercase text-slate-400 mb-0.5">Color</p>
                                    <p
                                        class="text-xs font-bold text-slate-700 uppercase truncate flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-black"></span> {{ jo.color }}
                                    </p>
                                </div>
                                <!-- ── RECIPE (mobile) ── -->
                                <div>
                                    <p class="text-[9px] font-black uppercase text-slate-400 mb-1">Recipe Number(s)</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template v-if="jo.extracted_recipes && jo.extracted_recipes.length > 0">
                                            <div v-for="(rec, idx) in jo.extracted_recipes" :key="idx"
                                                class="flex flex-col gap-0.5">
                                                <span
                                                    class="text-[10px] font-mono font-bold text-slate-700 bg-white border border-slate-200 px-1.5 py-0.5 rounded">
                                                    REC-{{ String(rec).padStart(4, '0') }}
                                                </span>
                                                <span v-if="idx === 0 && jo.recipe && jo.recipe.yarn_type"
                                                    class="text-[8px] text-slate-400 font-medium leading-none truncate max-w-[110px]">
                                                    {{ jo.recipe.yarn_type }}
                                                </span>
                                            </div>
                                        </template>
                                        <span v-else
                                            class="text-[10px] font-bold text-slate-500 font-mono bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">
                                            N/A
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Total Amount (mobile) ── -->
                            <div class="pl-2">
                                <p class="text-[9px] font-black uppercase text-slate-400 mb-0.5">Total Amount</p>
                                <p class="text-sm font-black text-blue-600">₱{{ formatPrice(jo.total_amount) }}</p>
                            </div>

                            <div class="flex items-center gap-2 pl-2 mt-2">
                                <button @click="openSummary(jo)"
                                    class="flex-1 py-3.5 bg-slate-50 text-slate-600 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-colors flex justify-center items-center gap-2 shadow-sm">
                                    <Eye class="h-4 w-4" /> Summary
                                </button>
                                <button @click="openConfirm(jo, 'scm')" :disabled="pushing[jo.id]"
                                    class="flex-1 py-3.5 bg-black hover:bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-colors disabled:opacity-50 flex justify-center items-center gap-2 shadow-lg shadow-black/10">
                                    <Loader2 v-if="pushing[jo.id]" class="h-4 w-4 animate-spin" />
                                    Push SCM
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop table -->
                    <div
                        class="hidden md:block bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                        <table class="w-full text-left">
                            <thead
                                class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100">
                                <tr>
                                    <th class="px-8 py-5">Order Identifiers</th>
                                    <th class="px-6 py-5">Product Specifications</th>
                                    <th class="px-6 py-5 text-center">Volume / Value</th>
                                    <th class="px-8 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="jo in filteredPendingOrders" :key="jo.id"
                                    class="hover:bg-slate-50/50 transition-colors">

                                    <!-- Order identifiers -->
                                    <td class="px-8 py-6">
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="h-10 w-10 mt-1 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center font-black text-xs">
                                                JO
                                            </div>
                                            <div class="space-y-2.5">
                                                <div>
                                                    <span
                                                        class="text-[9px] font-black uppercase tracking-widest text-slate-400 block mb-0.5">Client
                                                        Name</span>
                                                    <p class="text-sm font-black text-black">{{ jo.client?.company_name
                                                    }}</p>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <div
                                                        class="flex items-center gap-1.5 bg-blue-50 border border-blue-100 px-2 py-1 rounded-md">
                                                        <span class="text-[9px] font-black uppercase text-blue-400">JO
                                                            Number:</span>
                                                        <span class="text-xs font-bold text-blue-600 uppercase">{{
                                                            jo.jo_number }}</span>
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-2 py-1 rounded-md">
                                                        <span class="text-[9px] font-black uppercase text-slate-400">PO
                                                            Number:</span>
                                                        <span class="text-[10px] font-mono font-bold text-slate-600">{{
                                                            jo.purchase_order_id
                                                        }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Product specs + Recipe -->
                                    <td class="px-6 py-6">
                                        <div class="space-y-2.5">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-[9px] font-black uppercase text-slate-400 w-14">Color:</span>
                                                <span
                                                    class="text-xs font-bold text-slate-700 uppercase flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-black"></span> {{ jo.color
                                                    }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-[9px] font-black uppercase text-slate-400 w-14">Type:</span>
                                                <span class="text-[10px] font-bold text-slate-500 uppercase">{{
                                                    jo.yarn_type }}</span>
                                            </div>

                                            <!-- ── RECIPE DISPLAY (desktop) ── -->
                                            <div
                                                class="flex items-start gap-2 bg-slate-50 px-2 py-1.5 rounded-md w-fit border border-slate-100">
                                                <span
                                                    class="text-[9px] font-black uppercase text-slate-400 mt-0.5 flex-shrink-0">Recipe(s):</span>
                                                <div class="flex flex-wrap gap-1 max-w-[220px]">
                                                    <template
                                                        v-if="jo.extracted_recipes && jo.extracted_recipes.length > 0">
                                                        <div v-for="(rec, idx) in jo.extracted_recipes" :key="idx"
                                                            class="flex flex-col gap-0.5">
                                                            <span
                                                                class="text-[9px] font-mono text-slate-700 font-bold bg-white border border-slate-200 px-1.5 py-0.5 rounded">
                                                                REC-{{ String(rec).padStart(4, '0') }}
                                                            </span>
                                                            <span v-if="idx === 0 && jo.recipe && jo.recipe.yarn_type"
                                                                class="text-[8px] text-slate-400 font-medium leading-none truncate max-w-[110px]">
                                                                {{ jo.recipe.yarn_type }}
                                                            </span>
                                                            <span v-if="idx === 0 && jo.recipe && jo.recipe.dye_color"
                                                                class="text-[8px] text-blue-400 font-medium leading-none truncate max-w-[110px]">
                                                                {{ jo.recipe.dye_color }}
                                                            </span>
                                                        </div>
                                                    </template>
                                                    <span v-else
                                                        class="text-[9px] font-mono text-slate-500 font-bold">N/A</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Volume / Value -->
                                    <td class="px-6 py-6 text-center">
                                        <p class="text-sm font-black text-black">{{ jo.quantity }} kg</p>
                                        <p class="text-[10px] font-bold text-slate-400 mt-1">₱{{
                                            formatPrice(jo.total_amount) }}</p>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="openSummary(jo)"
                                                class="p-3 text-slate-400 hover:text-black transition-colors rounded-xl hover:bg-white border border-transparent hover:border-slate-200 shadow-sm">
                                                <Eye class="h-4 w-4" />
                                            </button>
                                            <button @click="openConfirm(jo, 'scm')" :disabled="pushing[jo.id]"
                                                class="px-5 py-3 bg-black hover:bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all disabled:opacity-50 flex items-center gap-2 shadow-md">
                                                <Loader2 v-if="pushing[jo.id]" class="h-3 w-3 animate-spin" />
                                                Push SCM
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <div v-if="filteredPendingOrders.length === 0"
                        class="bg-white rounded-[2.5rem] border border-dashed border-slate-200 p-16 text-center">
                        <div
                            class="mx-auto h-16 w-16 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mb-4">
                            <Search class="h-8 w-8" />
                        </div>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No pending orders found
                        </p>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════
                     PUSHED TAB
                     ══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'pushed'" class="animate-in">

                    <!-- Mobile cards -->
                    <div class="md:hidden space-y-4">
                        <div v-for="order in filteredPushedOrders" :key="order.id"
                            class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-4">

                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">
                                        Client Name</p>
                                    <h3 class="text-sm font-black text-black">{{ order.client?.company_name }}</h3>
                                </div>
                                <span
                                    class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-blue-100">
                                    {{ order.pushed_to }}
                                </span>
                            </div>

                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">JO
                                    Number</p>
                                <p class="text-xs font-mono font-bold text-slate-700">{{ order.jo_number }}</p>
                            </div>

                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">PO
                                    Number</p>
                                <p class="text-xs font-mono font-bold text-slate-700">{{ order.purchase_order_id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop table -->
                    <div
                        class="hidden md:block bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                        <table class="w-full text-left">
                            <thead
                                class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100">
                                <tr>
                                    <th class="px-8 py-5">Client Information</th>
                                    <th class="px-8 py-5">Reference Details</th>
                                    <th class="px-8 py-5 text-right">Status / Location</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="order in filteredPushedOrders" :key="order.id"
                                    class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-8 py-6">
                                        <span class="text-[9px] font-black uppercase text-slate-400 block mb-0.5">Client
                                            Name</span>
                                        <span class="font-black text-black text-sm">{{ order.client?.company_name
                                        }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-wrap gap-2">
                                            <div>
                                                <span
                                                    class="text-[9px] font-black uppercase text-slate-400 block mb-0.5">JO
                                                    Number</span>
                                                <span
                                                    class="font-mono text-sm font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-md border border-blue-100">{{
                                                        order.jo_number }}</span>
                                            </div>
                                            <div>
                                                <span
                                                    class="text-[9px] font-black uppercase text-slate-400 block mb-0.5">PO
                                                    Number</span>
                                                <span
                                                    class="font-mono text-sm font-bold text-slate-600 bg-slate-50 px-2 py-1 rounded-md border border-slate-100">{{
                                                        order.purchase_order_id }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-blue-100">
                                            <Check class="h-3 w-3" />
                                            {{ order.pushed_to }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="filteredPushedOrders.length === 0"
                        class="bg-white rounded-[2.5rem] border border-dashed border-slate-200 p-16 text-center">
                        <div
                            class="mx-auto h-16 w-16 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mb-4">
                            <Check class="h-8 w-8" />
                        </div>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No pushed orders yet</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             SUCCESS TOAST NOTIFICATION
             ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="toast">
                <div v-if="toast.show"
                    class="fixed bottom-6 right-6 z-[200] flex items-center gap-3 bg-black text-white px-6 py-4 rounded-2xl shadow-2xl shadow-black/20 max-w-sm">
                    <div class="h-8 w-8 rounded-xl flex items-center justify-center flex-shrink-0"
                        :class="toast.type === 'error' ? 'bg-red-500' : 'bg-green-500'">
                        <Check v-if="toast.type !== 'error'" class="h-4 w-4" />
                        <X v-else class="h-4 w-4" />
                    </div>
                    <p class="text-sm font-bold leading-snug">{{ toast.message }}</p>
                </div>
            </Transition>
        </Teleport>

        <!-- ══════════════════════════════════════════════════════════════════
             PUSH CONFIRMATION MODAL
             ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="confirmModal.show"
                    class="fixed inset-0 z-[150] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm"
                    @click.self="confirmModal.show = false">
                    <div
                        class="bg-white w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden animate-zoom-in flex flex-col">

                        <!-- Modal Header -->
                        <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Confirm Action
                                    </p>
                                </div>
                                <h3 class="font-black text-xl text-black tracking-tight">
                                    Push to {{ confirmModal.module === 'scm' ? 'SCM' : 'Order Management' }}?
                                </h3>
                            </div>
                            <button @click="confirmModal.show = false"
                                class="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-black rounded-2xl transition-colors">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-8 space-y-4 bg-[#FAFAFA]">
                            <p class="text-sm text-slate-500 font-medium leading-relaxed">
                                You are about to forward this Job Order to
                                <strong class="text-black">{{ confirmModal.module === 'scm' ? 'Supply Chain Management (SCM)' : 'Order Management' }}</strong>.
                                This action will move the order out of the pending queue.
                            </p>

                            <!-- Order Summary Card -->
                            <div v-if="confirmModal.order" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Client</span>
                                    <span class="text-sm font-black text-black">{{ confirmModal.order.client?.company_name }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-100 pt-3">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">JO Number</span>
                                    <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-md border border-blue-100">
                                        {{ confirmModal.order.jo_number }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-100 pt-3">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">PO Number</span>
                                    <span class="text-xs font-mono font-bold text-slate-600 bg-slate-50 px-2 py-1 rounded-md border border-slate-100">
                                        {{ confirmModal.order.purchase_order_id ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-100 pt-3">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Volume</span>
                                    <span class="text-sm font-black text-black">{{ confirmModal.order.quantity }} kg</span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-100 pt-3">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Amount</span>
                                    <span class="text-sm font-black text-blue-600">₱{{ formatPrice(confirmModal.order.total_amount) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-6 border-t border-slate-100 bg-white flex gap-3">
                            <button @click="confirmModal.show = false"
                                class="flex-1 py-4 rounded-2xl bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-colors">
                                Cancel
                            </button>
                            <button @click="executePush"
                                :disabled="pushing[confirmModal.order?.id]"
                                class="flex-1 py-4 rounded-2xl bg-black hover:bg-blue-600 text-white text-xs font-black uppercase tracking-widest transition-colors shadow-lg shadow-black/10 flex items-center justify-center gap-2 disabled:opacity-50">
                                <Loader2 v-if="pushing[confirmModal.order?.id]" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                Confirm Push
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ══════════════════════════════════════════════════════════════════
             SUMMARY MODAL
             ══════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="summaryModal.show"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/40 backdrop-blur-sm"
                @click.self="summaryModal.show = false">
                <div
                    class="bg-white w-full max-w-xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-zoom-in flex flex-col max-h-[90vh]">

                    <!-- Modal header -->
                    <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-start bg-white">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-2 w-2 rounded-full bg-yellow-400"></span>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">JO Number
                                </p>
                            </div>
                            <h3 class="font-black text-2xl text-black tracking-tight uppercase">{{
                                summaryModal.data.jo_number
                            }}</h3>
                        </div>
                        <button @click="summaryModal.show = false"
                            class="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-black rounded-2xl transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="p-8 space-y-6 overflow-y-auto bg-[#FAFAFA]">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-5 bg-white border border-slate-200 rounded-3xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Client
                                    Name</p>
                                <p class="font-black text-black text-sm">
                                    {{ summaryModal.data.client?.company_name }}
                                </p>
                            </div>
                            <div class="p-5 bg-white border border-slate-200 rounded-3xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">PO Number
                                </p>
                                <p
                                    class="font-bold font-mono text-slate-700 text-sm bg-slate-50 px-2 py-1 rounded-md w-fit border border-slate-100">
                                    {{ summaryModal.data.purchase_order_id }}</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <h4
                                class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] flex items-center gap-2">
                                <span class="h-px bg-slate-200 flex-1"></span> Item Details <span
                                    class="h-px bg-slate-200 flex-1"></span>
                            </h4>

                            <div class="bg-white border border-slate-200 rounded-3xl p-6">
                                <div class="flex justify-between items-start mb-6">
                                    <div class="space-y-4">
                                        <div>
                                            <p
                                                class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">
                                                Color</p>
                                            <p class="text-lg font-black text-black uppercase leading-none">{{
                                                summaryModal.data.color }}</p>
                                        </div>
                                        <div class="flex flex-col gap-3">
                                            <div>
                                                <p
                                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">
                                                    Type</p>
                                                <p class="text-xs font-bold text-slate-500 uppercase">{{
                                                    summaryModal.data.yarn_type }}</p>
                                            </div>

                                            <!-- ── RECIPE in Summary Modal ── -->
                                            <div>
                                                <p
                                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                                    Recipe Number(s)</p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <template
                                                        v-if="summaryModal.data.extracted_recipes && summaryModal.data.extracted_recipes.length > 0">
                                                        <div v-for="(rec, idx) in summaryModal.data.extracted_recipes"
                                                            :key="idx" class="flex flex-col gap-0.5">
                                                            <span
                                                                class="text-[10px] font-mono font-bold text-slate-700 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-100">
                                                                REC-{{ String(rec).padStart(4, '0') }}
                                                            </span>
                                                            <span
                                                                v-if="idx === 0 && summaryModal.data.recipe?.yarn_type"
                                                                class="text-[9px] text-slate-400 font-medium truncate max-w-[160px]">
                                                                {{ summaryModal.data.recipe.yarn_type }}
                                                            </span>
                                                            <span
                                                                v-if="idx === 0 && summaryModal.data.recipe?.dye_color"
                                                                class="text-[9px] text-blue-400 font-medium truncate max-w-[160px]">
                                                                {{ summaryModal.data.recipe.dye_color }}
                                                            </span>
                                                        </div>
                                                    </template>
                                                    <span v-else
                                                        class="text-[10px] font-mono font-bold text-slate-500">N/A</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                            Volume
                                        </p>
                                        <span
                                            class="block text-2xl font-black text-blue-600 tracking-tighter leading-none">{{
                                                summaryModal.data.quantity }}<span
                                                class="text-sm text-blue-400 ml-1">kg</span></span>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Est.
                                        Amount</span>
                                    <span class="text-base font-black text-black">₱{{
                                        formatPrice(summaryModal.data.total_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-white flex gap-3">
                        <button @click="summaryModal.show = false"
                            class="flex-1 py-4 rounded-2xl bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-colors">
                            Close Summary
                        </button>
                        <button @click="openConfirmFromSummary"
                            class="flex-1 py-4 rounded-2xl bg-black text-white text-xs font-black uppercase tracking-widest hover:bg-blue-600 transition-colors shadow-lg shadow-black/10 flex items-center justify-center gap-2">
                            <Send class="h-4 w-4" />
                            Push to SCM
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Send, RefreshCw, Search, Loader2, X, Eye, Check } from 'lucide-vue-next';

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
    salesOrders: { type: Array, default: () => [] },
    pushedOrders: { type: Array, default: () => [] },
});

// ── State ──────────────────────────────────────────────────────────────────
const activeTab = ref('pending');
const searchQuery = ref('');
const pushing = ref({});
const summaryModal = ref({ show: false, data: {} });

// Confirmation modal state
const confirmModal = ref({ show: false, order: null, module: null });

// Toast notification state
const toast = ref({ show: false, message: '', type: 'success' });

// ── Computed lists ─────────────────────────────────────────────────────────
const localPending = computed(() => props.salesOrders || []);
const localPushed = computed(() => props.pushedOrders || []);

const filteredPendingOrders = computed(() => {
    if (!searchQuery.value) return localPending.value;
    const s = searchQuery.value.toLowerCase();
    return localPending.value.filter(
        (jo) =>
            jo.jo_number?.toLowerCase().includes(s) ||
            jo.purchase_order_id?.toLowerCase().includes(s) ||
            jo.client?.company_name?.toLowerCase().includes(s) ||
            jo.color?.toLowerCase().includes(s) ||
            jo.design?.toLowerCase().includes(s) ||
            (jo.extracted_recipes ?? []).some(r =>
                String(r).includes(s) ||
                ('rec-' + String(r).padStart(4, '0')).includes(s)
            )
    );
});

const filteredPushedOrders = computed(() => {
    if (!searchQuery.value) return localPushed.value;
    const s = searchQuery.value.toLowerCase();
    return localPushed.value.filter(
        (po) =>
            po.jo_number?.toLowerCase().includes(s) ||
            po.purchase_order_id?.toLowerCase().includes(s) ||
            po.client?.company_name?.toLowerCase().includes(s) ||
            po.pushed_to?.toLowerCase().includes(s)
    );
});

// ── Toast helper ───────────────────────────────────────────────────────────
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 3500);
};

// ── Helpers ────────────────────────────────────────────────────────────────
const openSummary = (jo) => {
    summaryModal.value = { show: true, data: jo };
};

// Open confirm modal from the Summary modal's push button
const openConfirmFromSummary = () => {
    const order = summaryModal.value.data;
    summaryModal.value.show = false;
    confirmModal.value = { show: true, order, module: 'scm' };
};

// Open confirmation modal (from table / card buttons)
const openConfirm = (order, module) => {
    confirmModal.value = { show: true, order, module };
};

// Execute the actual push after user confirms
const executePush = () => {
    const { order, module } = confirmModal.value;
    if (!order) return;

    confirmModal.value.show = false;
    pushing.value[order.id] = true;

    router.post(
        route(module === 'scm' ? 'eco.push.scm' : 'eco.push.ordermgmt', order.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast(
                    `Order ${order.jo_number} successfully pushed to ${module === 'scm' ? 'SCM' : 'Order Management'}!`
                );
                // Auto-switch to the "Already Pushed" tab
                activeTab.value = 'pushed';
            },
            onError: (errors) => {
                const msg = errors?.error ?? 'Failed to push order. Please try again.';
                showToast(msg, 'error');
            },
            onFinish: () => {
                delete pushing.value[order.id];
            },
        }
    );
};

const formatPrice = (v) =>
    Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });

const refreshData = () => router.reload();
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.animate-in {
    animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.animate-zoom-in {
    animation: zoomIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Toast transition */
.toast-enter-active,
.toast-leave-active {
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(1rem) scale(0.95);
}

/* Confirmation modal fade */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>