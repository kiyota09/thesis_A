<template>
    <Head :title="`Inquiry #${inquiry.id} — ${inquiry.client?.company_name}`" />
    <AuthenticatedLayout>
        <div class="h-[calc(100vh-64px)] flex flex-col overflow-hidden bg-white">

            <!-- ── Header ──────────────────────────────────────────── -->
            <div class="flex items-center gap-2 sm:gap-3 px-4 sm:px-6 py-3 bg-white border-b border-gray-100 flex-shrink-0">
                <Link href="/dashboard/eco/inquiries"
                    class="p-2 rounded-xl hover:bg-gray-100 transition text-gray-400 hover:text-gray-700 flex-shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div class="flex-1 min-w-0">
                    <h1 class="text-sm sm:text-base font-bold text-gray-900 truncate leading-tight">
                        {{ inquiry.client?.company_name }}
                        <span class="text-gray-300 mx-1">·</span>
                        <span class="text-blue-600">
                            {{ isBulkInquiry ? `${parsedProducts.length} Products` : inquiry.product?.name }}
                        </span>
                    </h1>
                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mt-0.5 truncate">
                        {{ isBulkInquiry ? 'Bulk Inquiry' : `SKU: ${inquiry.product?.sku}` }}
                        · {{ inquiry.client?.email }}
                    </p>
                </div>
                <span :class="statusBadge(inquiry.status)" class="flex-shrink-0 hidden sm:inline-flex">
                    {{ formatStatus(inquiry.status) }}
                </span>
                <button @click="openRejectModal"
                    class="flex items-center gap-1.5 px-3 py-2 border border-red-200 text-red-500 rounded-xl text-xs font-semibold uppercase hover:bg-red-50 transition flex-shrink-0">
                    <XCircle class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">Reject</span>
                </button>
            </div>

            <!-- ── Mobile Tab Bar ──────────────────────────────────── -->
            <div class="flex lg:hidden border-b border-gray-100 bg-white flex-shrink-0">
                <button @click="mobilePanel = 'chat'"
                    :class="mobilePanel === 'chat' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                    class="flex-1 py-2.5 text-[11px] font-semibold uppercase tracking-wider transition">
                    Conversation
                </button>
                <button @click="mobilePanel = 'actions'"
                    :class="mobilePanel === 'actions' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                    class="flex-1 py-2.5 text-[11px] font-semibold uppercase tracking-wider transition">
                    Actions
                </button>
            </div>

            <!-- ── Main Layout ─────────────────────────────────────── -->
            <div class="flex flex-1 overflow-hidden">

                <!-- Left: Conversation ─────────────────────────────── -->
                <div :class="mobilePanel !== 'chat' ? 'hidden lg:flex' : 'flex'"
                    class="flex-1 flex-col overflow-hidden border-r border-gray-100 bg-white min-w-0">

                    <!-- Messages -->
                    <div ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 bg-gray-50/40">
                        <div v-for="msg in inquiry.messages" :key="msg.id"
                            class="flex flex-col"
                            :class="msg.sender_type === 'client' ? 'items-end' : 'items-start'">

                            <!-- System event pill -->
                            <div v-if="msg.is_system_event" class="w-full flex justify-center my-3">
                                <div class="bg-blue-50 border border-blue-100 px-4 py-2 rounded-xl max-w-[90%] sm:max-w-[80%] shadow-sm">
                                    <p class="text-[9px] font-semibold uppercase tracking-wide text-blue-600 text-center whitespace-pre-wrap leading-relaxed">
                                        {{ msg.message }}
                                    </p>
                                    <!-- Attachments in system event -->
                                    <div v-if="msg.attachments?.length" class="mt-2 space-y-1.5">
                                        <div v-for="file in msg.attachments" :key="file.id" class="relative">
                                            <img v-if="file.file_type?.startsWith('image/')"
                                                :src="getFullUrl(file.file_path)"
                                                class="max-w-full rounded-lg cursor-pointer border border-blue-100"
                                                @click="openImagePreview(file)" />
                                            <a v-else :href="getFullUrl(file.file_path)" target="_blank"
                                                class="flex items-center gap-2 p-2 bg-white/60 rounded-lg text-xs font-medium hover:bg-white transition">
                                                <FileText class="h-3.5 w-3.5 text-blue-400 flex-shrink-0" />
                                                <span class="truncate text-blue-700">{{ file.file_name }}</span>
                                            </a>
                                            <div class="absolute bottom-1 right-1 flex gap-1">
                                                <button v-if="file.approved_by_client && !file.is_po"
                                                    @click="openRecipeModal(file)"
                                                    class="px-2 py-1 bg-blue-600 text-white rounded-lg text-[8px] font-bold uppercase shadow-sm hover:bg-blue-700 transition">
                                                    Create Recipe
                                                </button>
                                                <button v-if="file.is_po"
                                                    @click="openJobOrderModal(file)"
                                                    class="px-2 py-1 bg-amber-400 text-amber-900 rounded-lg text-[8px] font-bold uppercase shadow-sm hover:bg-amber-500 transition">
                                                    Create Job Order
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Regular message -->
                            <div v-else
                                :class="msg.sender_type === 'client'
                                    ? 'bg-blue-600 text-white rounded-2xl rounded-tr-sm'
                                    : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-tl-sm shadow-sm'"
                                class="max-w-[80%] sm:max-w-[65%] px-4 py-3">
                                <p class="text-[9px] font-semibold uppercase tracking-wide opacity-60 mb-1">
                                    {{ msg.sender_type === 'client' ? inquiry.client?.company_name : 'ECO Team' }}
                                </p>
                                <p class="text-xs whitespace-pre-wrap leading-relaxed">{{ msg.message }}</p>
                                <!-- Attachments -->
                                <div v-if="msg.attachments?.length" class="mt-2 space-y-1.5">
                                    <div v-for="file in msg.attachments" :key="file.id" class="relative">
                                        <img v-if="file.file_type?.startsWith('image/')"
                                            :src="getFullUrl(file.file_path)"
                                            class="max-w-full rounded-lg cursor-pointer border border-white/20"
                                            @click="openImagePreview(file)" />
                                        <a v-else :href="getFullUrl(file.file_path)" target="_blank"
                                            class="flex items-center gap-2 p-2 bg-white/10 rounded-lg text-xs font-medium hover:bg-white/20 transition">
                                            <FileText class="h-3.5 w-3.5 flex-shrink-0" />
                                            <span class="truncate">{{ file.file_name }}</span>
                                        </a>
                                        <div class="absolute bottom-1 right-1 flex gap-1">
                                            <button v-if="file.approved_by_client && !file.is_po"
                                                @click="openRecipeModal(file)"
                                                class="px-2 py-1 bg-blue-600 text-white rounded-lg text-[8px] font-bold uppercase shadow-sm hover:bg-blue-700 transition">
                                                Create Recipe
                                            </button>
                                            <button v-if="file.is_po"
                                                @click="openJobOrderModal(file)"
                                                class="px-2 py-1 bg-amber-400 text-amber-900 rounded-lg text-[8px] font-bold uppercase shadow-sm hover:bg-amber-500 transition">
                                                Create Job Order
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[9px] opacity-50 mt-1.5 text-right">{{ formatTime(msg.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Reply box -->
                    <div class="flex-shrink-0 border-t border-gray-100 p-3 sm:p-4 bg-white">
                        <!-- File previews -->
                        <div v-if="selectedFiles.length" class="flex gap-2 mb-3 overflow-x-auto pb-1 scrollbar-hide">
                            <div v-for="(file, i) in selectedFiles" :key="i"
                                class="relative h-12 w-12 flex-shrink-0 bg-gray-100 rounded-xl border border-gray-200 overflow-hidden">
                                <img v-if="file.type.startsWith('image/')" :src="objectUrl(file)"
                                    class="h-full w-full object-cover" />
                                <div v-else class="h-full w-full flex items-center justify-center">
                                    <FileText class="h-5 w-5 text-gray-400" />
                                </div>
                                <button @click="selectedFiles.splice(i, 1)"
                                    class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 shadow">
                                    <X class="h-2.5 w-2.5" />
                                </button>
                            </div>
                        </div>
                        <!-- Input row -->
                        <div class="flex items-center gap-2 bg-gray-50 rounded-2xl px-3 py-2 border border-gray-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-500/10 transition">
                            <input v-model="newMessage" type="text"
                                placeholder="Reply to client…"
                                class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-gray-800 placeholder:text-gray-400"
                                @keydown.enter.prevent="sendMessage" />
                            <button type="button" @click="$refs.fileInput.click()"
                                class="p-1.5 hover:bg-gray-200 rounded-lg transition text-gray-400 hover:text-gray-600">
                                <Paperclip class="h-4 w-4" />
                            </button>
                            <input ref="fileInput" type="file" class="hidden" multiple
                                @change="e => { selectedFiles.push(...Array.from(e.target.files)); e.target.value = ''; }" />
                            <button type="button" @click="sendMessage"
                                :disabled="sending || (!newMessage.trim() && !selectedFiles.length)"
                                class="p-2 bg-blue-600 text-white rounded-xl disabled:opacity-40 transition hover:bg-blue-700">
                                <Send v-if="!sending" class="h-4 w-4" />
                                <Loader2 v-else class="h-4 w-4 animate-spin" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Actions Panel ──────────────────────────── -->
                <div :class="mobilePanel !== 'actions' ? 'hidden lg:block' : 'block'"
                    class="w-full lg:w-[440px] xl:w-[500px] flex-shrink-0 overflow-y-auto bg-gray-50 p-4 space-y-4">

                    <!-- ── Quick Actions ──────────────────────────── -->
                    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                        <div class="flex items-center gap-2 px-5 py-3.5 border-b border-gray-100">
                            <ClipboardList class="h-4 w-4 text-amber-500 flex-shrink-0" />
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Actions</h3>
                        </div>
                        <div class="p-4 space-y-2.5">
                            <button @click="openQuotationModal"
                                class="w-full py-3 bg-blue-600 text-white rounded-xl text-xs font-bold uppercase tracking-wide hover:bg-blue-700 transition flex items-center justify-center gap-2 shadow-sm">
                                <FileText class="h-4 w-4" /> Issue Quotation
                            </button>
                            <button @click="openMeetingModal"
                                class="w-full py-3 bg-amber-400 text-amber-900 rounded-xl text-xs font-bold uppercase tracking-wide hover:bg-amber-500 transition flex items-center justify-center gap-2 shadow-sm">
                                <Calendar class="h-4 w-4" /> Set Meeting
                            </button>
                        </div>
                    </div>

                    <!-- ── Recipes ─────────────────────────────────── -->
                    <template v-if="recipes.length > 0">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 px-1">
                            Recipes ({{ recipes.length }})
                        </p>
                        <div v-for="recipe in recipes" :key="recipe.id"
                            class="bg-white rounded-xl border border-gray-100 p-3.5 shadow-sm">
                            <p class="text-xs font-bold text-gray-900">{{ recipe.product?.name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ recipe.yarn_type }} · {{ recipe.dye_color }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ recipe.weave_design }}</p>
                        </div>
                    </template>

                    <!-- ── Previous Quotations ────────────────────── -->
                    <template v-if="quotations.length > 0">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 px-1">
                            Quotations ({{ quotations.length }})
                        </p>

                        <div v-for="q in quotations" :key="q.id"
                            class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                            <!-- Status bar -->
                            <div class="flex items-center justify-between px-4 py-2.5"
                                :class="q.status === 'accepted' ? 'bg-emerald-500'
                                    : q.status === 'rejected' ? 'bg-red-500'
                                    : 'bg-blue-600'">
                                <span class="font-mono text-[10px] font-semibold text-white">
                                    {{ q.quotation_number }}
                                </span>
                                <span class="text-[9px] font-bold uppercase text-white/80 bg-black/15 px-2 py-0.5 rounded-full">
                                    {{ q.status }}
                                </span>
                            </div>
                            <div class="p-3 space-y-2.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[9px] font-semibold rounded-full px-2 py-0.5"
                                        :class="q.vat_type === 'inclusive'
                                            ? 'bg-blue-50 text-blue-600 border border-blue-100'
                                            : 'bg-gray-100 text-gray-500'">
                                        {{ q.vat_type === 'inclusive' ? 'VAT Inclusive (+12%)' : 'VAT Exclusive' }}
                                    </span>
                                    <span class="text-[9px] text-gray-400 font-medium uppercase">
                                        {{ q.payment_terms }}
                                    </span>
                                </div>
                                <div v-for="(group, fabric) in groupItemsByFabric(q.items)" :key="fabric"
                                    class="border border-gray-100 rounded-xl overflow-hidden">
                                    <div class="bg-gray-50 px-3 py-2 flex items-center gap-2">
                                        <Package class="h-3 w-3 text-blue-500 flex-shrink-0" />
                                        <p class="text-[10px] font-semibold text-blue-700 uppercase tracking-wider truncate flex-1">
                                            {{ fabric }}
                                        </p>
                                        <span class="text-[9px] text-gray-400 font-medium">{{ group[0]?.kilos }}kg MOQ</span>
                                    </div>
                                    <div class="divide-y divide-gray-50">
                                        <div v-for="item in group" :key="item.id"
                                            class="px-3 py-2 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-2.5 h-2.5 rounded-full border flex-shrink-0"
                                                    :style="colorDotStyle(item.color)"></div>
                                                <span class="text-[9px] font-semibold text-gray-700 uppercase">
                                                    {{ item.color }}
                                                </span>
                                            </div>
                                            <p class="text-[10px] font-bold text-blue-600">
                                                ₱{{ formatPeso(item.unit_price) }}/kg
                                                <span v-if="q.vat_type === 'inclusive'"
                                                    class="text-[8px] text-gray-400 ml-1">inc. VAT</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="q.notes"
                                    class="text-[9px] text-gray-400 italic border-t border-gray-50 pt-2">
                                    {{ q.notes }}
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             IMAGE PREVIEW MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="imagePreviewModal.show"
                class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/95 backdrop-blur-md"
                @click.self="imagePreviewModal.show = false">
                <button @click="imagePreviewModal.show = false"
                    class="absolute top-5 right-5 p-2 bg-white/10 hover:bg-white/20 rounded-xl transition z-[210]">
                    <X class="h-5 w-5 text-white" />
                </button>
                <div class="w-full max-w-5xl h-[88vh] flex items-center justify-center">
                    <img v-if="imagePreviewModal.file"
                        :src="getFullUrl(imagePreviewModal.file.file_path)"
                        class="max-w-full max-h-full object-contain rounded-xl shadow-2xl" />
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             ISSUE QUOTATION MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showQuotationModal"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="showQuotationModal = false">
                <div class="bg-white w-full sm:max-w-2xl rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[95vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-gray-100 flex-shrink-0">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Issue Quotation</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ inquiry.client?.company_name }}</p>
                        </div>
                        <button @click="showQuotationModal = false"
                            class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-5 sm:p-6 overflow-y-auto flex-1">
                        <form @submit.prevent="submitQuotation" class="space-y-5">

                            <!-- VAT Type Toggle -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
                                    VAT Type
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="form.vat_type = 'exclusive'"
                                        :class="form.vat_type === 'exclusive'
                                            ? 'bg-gray-900 text-white border-gray-900'
                                            : 'bg-white text-gray-500 border-gray-200 hover:border-gray-400'"
                                        class="py-2.5 rounded-xl border-2 text-xs font-bold uppercase tracking-wide transition">
                                        VAT Exclusive
                                    </button>
                                    <button type="button" @click="form.vat_type = 'inclusive'"
                                        :class="form.vat_type === 'inclusive'
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : 'bg-white text-gray-500 border-gray-200 hover:border-blue-300'"
                                        class="py-2.5 rounded-xl border-2 text-xs font-bold uppercase tracking-wide flex items-center justify-center gap-1.5 transition">
                                        VAT Inclusive
                                        <span v-if="form.vat_type === 'inclusive'"
                                            class="bg-white/20 px-1.5 py-0.5 rounded text-[10px]">+12%</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Product rows -->
                            <div class="space-y-3">
                                <div v-for="(item, idx) in form.items" :key="idx"
                                    class="border border-gray-200 rounded-2xl overflow-hidden">
                                    <!-- Fabric name row -->
                                    <div class="flex items-center gap-2 bg-gray-50 px-3 py-2.5 border-b border-gray-200">
                                        <Package class="h-3.5 w-3.5 text-blue-500 flex-shrink-0" />
                                        <input v-model="item.fabric"
                                            class="flex-1 bg-transparent border-none p-0 text-xs font-bold text-gray-900 focus:ring-0 uppercase tracking-wide min-w-0"
                                            placeholder="Fabric / Product Name" required />
                                        <button v-if="!item.isDefault" type="button" @click="removeItem(idx)"
                                            class="p-1 text-gray-400 hover:text-red-500 flex-shrink-0 transition">
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>

                                    <!-- MOQ + Design -->
                                    <div class="grid grid-cols-2 gap-3 px-3 pt-3 pb-2">
                                        <div>
                                            <label class="block text-[10px] font-medium text-gray-400 uppercase tracking-wide mb-1">
                                                MOQ (kg)
                                            </label>
                                            <input v-model.number="item.kilos" type="number" min="0" step="0.01"
                                                class="w-full rounded-xl border border-gray-200 bg-gray-50 text-sm font-semibold py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition"
                                                placeholder="0.00" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-medium text-gray-400 uppercase tracking-wide mb-1">
                                                Design <span class="normal-case font-normal">(opt.)</span>
                                            </label>
                                            <input v-model="item.design" type="text"
                                                class="w-full rounded-xl border border-gray-200 bg-gray-50 text-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition"
                                                placeholder="e.g. Standard" />
                                        </div>
                                    </div>

                                    <!-- Prices per kg -->
                                    <div class="px-3 pb-3 space-y-2">
                                        <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">
                                            Price per kg
                                            <span v-if="form.vat_type === 'inclusive'" class="text-blue-500 ml-1">(VAT inclusive)</span>
                                        </p>
                                        <!-- White -->
                                        <div class="flex items-center gap-2 bg-gray-50 rounded-xl p-2.5 border border-gray-100">
                                            <div class="w-3.5 h-3.5 rounded-full border-2 border-gray-300 bg-white shadow-sm flex-shrink-0"></div>
                                            <span class="text-xs font-semibold text-gray-600 uppercase w-16 flex-shrink-0">White</span>
                                            <div class="flex-1 relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">₱</span>
                                                <input v-model.number="item.price_white" type="number" min="0" step="0.01"
                                                    class="w-full pl-6 pr-2 py-1.5 rounded-lg border border-gray-200 bg-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition"
                                                    placeholder="0.00" required />
                                            </div>
                                        </div>
                                        <!-- Light -->
                                        <div class="flex items-center gap-2 bg-amber-50/60 rounded-xl p-2.5 border border-amber-100">
                                            <div class="w-3.5 h-3.5 rounded-full border-2 border-amber-300 bg-amber-100 shadow-sm flex-shrink-0"></div>
                                            <span class="text-xs font-semibold text-gray-600 uppercase w-16 flex-shrink-0">Light</span>
                                            <div class="flex-1 relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">₱</span>
                                                <input v-model.number="item.price_light" type="number" min="0" step="0.01"
                                                    class="w-full pl-6 pr-2 py-1.5 rounded-lg border border-amber-200 bg-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-400 transition"
                                                    placeholder="0.00" required />
                                            </div>
                                        </div>
                                        <!-- Dark -->
                                        <div class="flex items-center gap-2 bg-gray-800/5 rounded-xl p-2.5 border border-gray-200">
                                            <div class="w-3.5 h-3.5 rounded-full border-2 border-gray-500 bg-gray-700 shadow-sm flex-shrink-0"></div>
                                            <span class="text-xs font-semibold text-gray-600 uppercase w-16 flex-shrink-0">Dark</span>
                                            <div class="flex-1 relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">₱</span>
                                                <input v-model.number="item.price_dark" type="number" min="0" step="0.01"
                                                    class="w-full pl-6 pr-2 py-1.5 rounded-lg border border-gray-300 bg-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-gray-500/20 focus:border-gray-400 transition"
                                                    placeholder="0.00" required />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add product -->
                            <button type="button" @click="addItem"
                                class="w-full py-2.5 border-2 border-dashed border-blue-200 text-blue-600 rounded-xl text-xs font-bold uppercase tracking-wide hover:bg-blue-50 transition flex items-center justify-center gap-2">
                                <Plus class="h-3.5 w-3.5" /> Add Another Product
                            </button>

                            <!-- Payment Terms -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                    Payment Terms *
                                </label>
                                <select v-model="form.payment_terms" required
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 text-sm py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition">
                                    <option value="">Select payment terms…</option>
                                    <option value="Cash on Delivery">Cash on Delivery</option>
                                    <option value="30 Days">30 Days</option>
                                    <option value="60 Days">60 Days</option>
                                    <option value="90 Days">90 Days</option>
                                    <option value="120 Days">120 Days</option>
                                    <option value="150 Days">150 Days</option>
                                </select>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                    Notes <span class="normal-case font-normal">(optional)</span>
                                </label>
                                <textarea v-model="form.notes" rows="2"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 text-sm p-3 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition"
                                    placeholder="Additional terms, delivery requirements…"></textarea>
                            </div>

                            <!-- Submit -->
                            <button type="submit" :disabled="submitting || !form.payment_terms"
                                class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold text-xs uppercase tracking-wide flex justify-center items-center gap-2 hover:bg-blue-700 disabled:opacity-40 transition shadow-sm">
                                <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                Send Quotation
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             SET MEETING MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="showMeetingModal"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="showMeetingModal = false">
                <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Schedule Meeting</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ inquiry.client?.company_name }}</p>
                        </div>
                        <button @click="showMeetingModal = false"
                            class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <form @submit.prevent="submitMeeting" class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                Date & Time *
                            </label>
                            <input v-model="meetingData.scheduled_at" type="datetime-local" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                Location *
                            </label>
                            <input v-model="meetingData.location" type="text"
                                placeholder="e.g., Zoom, Office, Google Meet" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                Meeting Type *
                            </label>
                            <select v-model="meetingData.type" required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition">
                                <option value="onsite">On‑site</option>
                                <option value="video">Video Call</option>
                                <option value="phone">Phone Call</option>
                            </select>
                        </div>
                        <button type="submit" :disabled="scheduling"
                            class="w-full py-3 bg-amber-400 text-amber-900 rounded-xl font-bold text-xs uppercase tracking-wide flex justify-center items-center gap-2 hover:bg-amber-500 disabled:opacity-50 transition shadow-sm">
                            <Loader2 v-if="scheduling" class="h-4 w-4 animate-spin" />
                            <Calendar v-else class="h-4 w-4" />
                            Send Invite
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             REJECT INQUIRY MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="rejectModal.show"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="rejectModal.show = false">
                <div class="bg-white w-full sm:max-w-sm rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Reject Inquiry</h3>
                            <p class="text-xs text-gray-400 mt-0.5">This action cannot be undone</p>
                        </div>
                        <button @click="rejectModal.show = false"
                            class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <form @submit.prevent="submitReject" class="p-5 space-y-4">
                        <textarea v-model="rejectModal.reason" rows="3" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-400 transition"
                            placeholder="Reason for rejection…"></textarea>
                        <div class="flex gap-3">
                            <button type="button" @click="rejectModal.show = false"
                                class="flex-1 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl text-xs font-semibold uppercase hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-xs font-bold uppercase hover:bg-red-600 transition">
                                Confirm Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             CREATE RECIPE MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="recipeModal.show"
                class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="recipeModal.show = false">
                <div class="bg-white w-full sm:max-w-xl rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[92vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Create Recipe</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ inquiry.client?.company_name }}</p>
                        </div>
                        <button @click="recipeModal.show = false"
                            class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="p-5 overflow-y-auto flex-1">
                        <form @submit.prevent="submitRecipe" class="space-y-4">
                            <!-- Client (readonly) -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Business Client</label>
                                <input type="text" :value="inquiry.client?.company_name" disabled
                                    class="w-full rounded-xl border border-gray-100 bg-gray-50 p-2.5 text-sm text-gray-500 cursor-not-allowed" />
                            </div>
                            <!-- Product -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Product *</label>
                                <select v-model="recipeForm.product_id" required
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition">
                                    <option value="" disabled>Select product</option>
                                    <option v-for="p in availableProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                            <!-- Yarn Type -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Yarn Type *</label>
                                <input v-model="recipeForm.yarn_type" type="text" required
                                    placeholder="e.g. 50% Cotton 50% Silk"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />
                            </div>
                            <!-- Dye Color -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Dye Color *</label>
                                <input v-model="recipeForm.dye_color" type="text" required
                                    placeholder="e.g. 30% White 70% Blue"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />
                            </div>
                            <!-- Weave Design -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Weave Design *</label>
                                <input v-model="recipeForm.weave_design" type="text" required
                                    placeholder="e.g. Twill 2/1"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />
                            </div>
                            <!-- Raw Materials -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Raw Materials *</label>
                                <div class="relative">
                                    <button type="button" @click="showMaterialsDropdown = !showMaterialsDropdown"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm text-left flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition">
                                        <span class="text-gray-700">{{ selectedMaterialsCount }} material(s) selected</span>
                                        <ChevronDown class="h-4 w-4 text-gray-400" />
                                    </button>
                                    <div v-if="showMaterialsDropdown"
                                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-52 overflow-y-auto">
                                        <div class="p-2">
                                            <div v-for="mat in materials" :key="mat.id"
                                                class="flex items-center gap-2.5 py-2 px-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition"
                                                @click="toggleMaterial(mat.id)">
                                                <input type="checkbox" :checked="recipeForm.materials.includes(mat.id)"
                                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                                <span class="text-sm text-gray-700">{{ mat.mat_id }} – {{ mat.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">Click to select or deselect materials</p>
                            </div>

                            <!-- Error -->
                            <div v-if="recipeModal.error"
                                class="p-3 bg-red-50 border border-red-100 text-red-600 rounded-xl text-xs">
                                {{ recipeModal.error }}
                            </div>

                            <!-- Submit -->
                            <button type="submit" :disabled="recipeModal.submitting"
                                class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold text-xs uppercase tracking-wide flex justify-center items-center gap-2 hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                                <Loader2 v-if="recipeModal.submitting" class="h-4 w-4 animate-spin" />
                                {{ recipeModal.submitting ? 'Saving…' : 'Create Recipe' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             CREATE JOB ORDER MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="jobOrderModal.show"
                class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="jobOrderModal.show = false">
                <div class="bg-white w-full sm:max-w-5xl rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[92vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-gray-100 flex-shrink-0">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Create Job Order</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ recipes.length }} recipe(s) available · {{ inquiry.client?.company_name }}
                            </p>
                        </div>
                        <button @click="jobOrderModal.show = false"
                            class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">
                        <!-- Left: PO Preview -->
                        <div class="w-full lg:w-1/2 border-b lg:border-b-0 lg:border-r border-gray-100 p-4 overflow-auto bg-gray-50 max-h-60 lg:max-h-none">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Purchase Order</p>
                            <div v-if="jobOrderModal.attachment">
                                <img v-if="jobOrderModal.attachment.file_type?.startsWith('image/')"
                                    :src="getFullUrl(jobOrderModal.attachment.file_path)"
                                    class="max-w-full border border-gray-200 rounded-xl shadow-sm" />
                                <div v-else class="p-6 bg-white rounded-xl border border-gray-200 flex flex-col items-center gap-3 shadow-sm">
                                    <FileText class="h-10 w-10 text-gray-300" />
                                    <a :href="getFullUrl(jobOrderModal.attachment.file_path)" target="_blank"
                                        class="text-blue-600 text-sm font-semibold hover:underline">View PDF</a>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Form -->
                        <div class="w-full lg:w-1/2 p-4 sm:p-5 overflow-auto">
                            <form @submit.prevent="submitJobOrder" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                        PO Number *
                                    </label>
                                    <input v-model="jobOrderForm.po_number" type="text" required
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />
                                </div>

                                <!-- Product items -->
                                <div v-for="(item, idx) in jobOrderForm.items" :key="idx"
                                    class="border border-gray-200 rounded-2xl p-4 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">
                                            Product {{ idx + 1 }}
                                        </span>
                                        <button v-if="jobOrderForm.items.length > 1" type="button"
                                            @click="removeJobOrderItem(idx)"
                                            class="text-xs text-red-400 hover:text-red-600 font-semibold transition">
                                            Remove
                                        </button>
                                    </div>

                                    <select v-model="item.product_id" required
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition">
                                        <option value="">Select Product</option>
                                        <option v-for="p in availableProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
                                    </select>

                                    <input v-model="item.color" type="text" placeholder="Color" required
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />

                                    <div>
                                        <select v-model="item.recipe_id"
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition">
                                            <option :value="null">
                                                {{ !item.product_id
                                                    ? '— Select a product first —'
                                                    : recipesForProduct(item.product_id).length === 0
                                                        ? '— No recipes found —'
                                                        : '— Select Recipe (optional) —'
                                                }}
                                            </option>
                                            <option v-for="r in recipesForProduct(item.product_id)" :key="r.id" :value="r.id">
                                                {{ r.yarn_type }} — {{ r.dye_color }}
                                                <template v-if="r.weave_design"> ({{ r.weave_design }})</template>
                                            </option>
                                        </select>
                                        <p v-if="item.product_id && recipesForProduct(item.product_id).length > 0"
                                            class="text-[9px] text-emerald-600 font-semibold mt-1">
                                            {{ recipesForProduct(item.product_id).length }} recipe(s) available
                                        </p>
                                        <p v-else-if="item.product_id && recipesForProduct(item.product_id).length === 0"
                                            class="text-[9px] text-amber-500 font-semibold mt-1">
                                            No recipe yet — create one via "Create Recipe" first.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <input v-model.number="item.kilos" type="number" placeholder="Kilos" required
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />
                                        <input v-model.number="item.unit_price" type="number" step="0.01"
                                            placeholder="Price per kg" required
                                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition" />
                                    </div>

                                    <div class="flex justify-end">
                                        <span class="text-xs font-bold text-blue-600">
                                            Total: ₱{{ ((item.kilos || 0) * (item.unit_price || 0)).toFixed(2) }}
                                        </span>
                                    </div>

                                    <textarea v-model="item.description" placeholder="Description (optional)" rows="2"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition"></textarea>
                                </div>

                                <button type="button" @click="addJobOrderItem"
                                    class="w-full py-2.5 border-2 border-dashed border-blue-200 text-blue-600 rounded-xl text-xs font-bold uppercase tracking-wide hover:bg-blue-50 transition flex items-center justify-center gap-2">
                                    + Add Product
                                </button>

                                <!-- Error -->
                                <div v-if="jobOrderModal.error"
                                    class="p-3 bg-red-50 border border-red-100 text-red-600 rounded-xl text-xs">
                                    {{ jobOrderModal.error }}
                                </div>

                                <!-- Submit -->
                                <button type="submit" :disabled="jobOrderModal.submitting"
                                    class="w-full py-3 bg-amber-400 text-amber-900 rounded-xl font-bold text-xs uppercase tracking-wide flex justify-center items-center gap-2 hover:bg-amber-500 disabled:opacity-50 transition shadow-sm">
                                    <Loader2 v-if="jobOrderModal.submitting" class="h-4 w-4 animate-spin" />
                                    {{ jobOrderModal.submitting ? 'Creating…' : 'Create Job Order' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import {
    ArrowLeft, Send, Loader2, FileText, X, Package, Trash2,
    Plus, Paperclip, ClipboardList, XCircle, Calendar, ChevronDown
} from 'lucide-vue-next';

const props = defineProps({
    inquiry:        { type: Object,  required: true },
    quotations:     { type: Array,   default: () => [] },
    parsedProducts: { type: Array,   default: () => [] },
    allProducts:    { type: Array,   default: () => [] },
    recipes:        { type: Array,   default: () => [] },
    materials:      { type: Array,   default: () => [] },
});

// Mobile panel toggle
const mobilePanel = ref('chat');

// Image preview modal
const imagePreviewModal = ref({ show: false, file: null });
const openImagePreview = (file) => {
    imagePreviewModal.value = { show: true, file };
};

// Materials dropdown state
const showMaterialsDropdown = ref(false);
const selectedMaterialsCount = computed(() => recipeForm.value.materials.length);

const toggleMaterial = (id) => {
    const index = recipeForm.value.materials.indexOf(id);
    if (index > -1) {
        recipeForm.value.materials.splice(index, 1);
    } else {
        recipeForm.value.materials.push(id);
    }
};

// Close dropdown when clicking outside
watch(showMaterialsDropdown, (val) => {
    if (val) {
        setTimeout(() => {
            const handler = (e) => {
                if (!e.target.closest('.relative')) {
                    showMaterialsDropdown.value = false;
                    document.removeEventListener('click', handler);
                }
            };
            document.addEventListener('click', handler);
        }, 100);
    }
});

const isBulkInquiry = computed(() =>
    props.inquiry.product?.name === 'Bulk Inquiry' || !props.inquiry.product
);

// Use allProducts for dropdown (they have valid IDs)
const availableProducts = computed(() => props.allProducts);

const formatStatus = (s) => (s ? s.replace(/_/g, ' ').toUpperCase() : 'OPEN');
const formatTime   = (d) => new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
const formatPeso   = (v) => Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const objectUrl    = (f) => URL.createObjectURL(f);

const getFullUrl = (path) => {
    if (!path) return '#';
    if (path.startsWith('http')) return path;
    const cleanPath = path.replace(/^\/?storage\//, '');
    return `/storage/${cleanPath}`;
};

const statusBadge = (s) => {
    const map = {
        open:           'bg-blue-100 text-blue-700',
        quotation_sent: 'bg-blue-100 text-blue-700',
        converted:      'bg-emerald-100 text-emerald-700',
        rejected:       'bg-red-100 text-red-700',
    };
    return (map[s] || 'bg-gray-100 text-gray-500') + ' px-3 py-1 rounded-full text-[9px] font-bold uppercase';
};

const colorDotStyle = (color) => {
    if (color === 'White')        return 'background-color:#f5f5f5; border-color:#d1d5db';
    if (color === 'Light Colors') return 'background-color:#fde68a; border-color:#fbbf24';
    if (color === 'Dark Colors')  return 'background-color:#374151; border-color:#1f2937';
    return 'background-color:#9ca3af; border-color:#6b7280';
};

const groupItemsByFabric = (items) =>
    (items || []).reduce((acc, item) => {
        (acc[item.fabric] = acc[item.fabric] || []).push(item);
        return acc;
    }, {});

const makeItem = (name = '', isDefault = false) => ({
    fabric:      name,
    design:      '',
    kilos:       0,
    price_white: 0,
    price_light: 0,
    price_dark:  0,
    isDefault,
});

const form = ref({
    vat_type:      'exclusive',
    payment_terms: '',
    notes:         '',
    items: props.parsedProducts?.length
        ? props.parsedProducts.map(p => makeItem(p.name, true))
        : [makeItem('', true)],
});

const submitting = ref(false);
const showQuotationModal = ref(false);

const openQuotationModal = () => {
    form.value.items = props.parsedProducts?.length
        ? props.parsedProducts.map(p => makeItem(p.name, true))
        : [makeItem('', true)];
    form.value.payment_terms = '';
    form.value.notes = '';
    form.value.vat_type = 'exclusive';
    showQuotationModal.value = true;
};

const addItem = () => {
    form.value.items.push(makeItem('', false));
};

const removeItem = (idx) => {
    if (!form.value.items[idx].isDefault) {
        form.value.items.splice(idx, 1);
    }
};

const submitQuotation = () => {
    submitting.value = true;
    router.post(route('eco.inquiry.quotation', props.inquiry.id), form.value, {
        onSuccess: () => {
            showQuotationModal.value = false;
        },
        onFinish: () => { submitting.value = false; },
    });
};

const messagesContainer = ref(null);
const newMessage        = ref('');
const sending           = ref(false);
const selectedFiles     = ref([]);

const scrollToBottom = () =>
    nextTick(() => {
        if (messagesContainer.value)
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    });

const sendMessage = () => {
    if (!newMessage.value.trim() && !selectedFiles.value.length) return;
    sending.value = true;
    const fd = new FormData();
    fd.append('message', newMessage.value || '');
    selectedFiles.value.forEach((f, i) => fd.append(`files[${i}]`, f));
    router.post(route('eco.inquiry.message', props.inquiry.id), fd, {
        forceFormData: true,
        onSuccess: () => { newMessage.value = ''; selectedFiles.value = []; scrollToBottom(); },
        onFinish:  () => { sending.value = false; },
    });
};

const showMeetingModal = ref(false);
const scheduling = ref(false);
const meetingData = ref({
    scheduled_at: '',
    location: '',
    type: 'video',
});

const openMeetingModal = () => {
    meetingData.value = { scheduled_at: '', location: '', type: 'video' };
    showMeetingModal.value = true;
};

const submitMeeting = () => {
    scheduling.value = true;
    router.post(route('eco.inquiry.meeting', props.inquiry.id), meetingData.value, {
        onSuccess: () => {
            showMeetingModal.value = false;
            scrollToBottom();
        },
        onFinish: () => { scheduling.value = false; },
    });
};

const rejectModal   = ref({ show: false, reason: '' });
const openRejectModal = () => { rejectModal.value = { show: true, reason: '' }; };
const submitReject  = () => {
    router.post(route('eco.inquiry.reject', props.inquiry.id), { reason: rejectModal.value.reason }, {
        onSuccess: () => { rejectModal.value.show = false; },
    });
};

const recipeModal = ref({
    show: false,
    submitting: false,
    attachment: null,
    error: null,
});

const recipeForm = ref({
    client_id: props.inquiry.client_id,
    product_id: '',
    yarn_type: '',
    dye_color: '',
    weave_design: '',
    materials: [],
});

const openRecipeModal = (attachment) => {
    const firstProductId = availableProducts.value[0]?.id || '';
    recipeForm.value = {
        client_id: props.inquiry.client_id,
        product_id: firstProductId,
        yarn_type: '',
        dye_color: '',
        weave_design: '',
        materials: [],
    };
    recipeModal.value = { show: true, submitting: false, attachment, error: null };
    showMaterialsDropdown.value = false;
};

const submitRecipe = () => {
    recipeModal.value.error = null;
    recipeModal.value.submitting = true;
    router.post(route('eco.attachment.create-recipe', recipeModal.value.attachment.id), recipeForm.value, {
        onSuccess: () => {
            recipeModal.value.show = false;
        },
        onError: (errors) => {
            const messages = [];
            for (const key in errors) {
                if (Array.isArray(errors[key])) {
                    messages.push(...errors[key]);
                } else {
                    messages.push(errors[key]);
                }
            }
            recipeModal.value.error = messages.join(' ') || 'Validation failed.';
        },
        onFinish: () => { recipeModal.value.submitting = false; }
    });
};

const jobOrderModal = ref({
    show: false,
    submitting: false,
    attachment: null,
    error: null,
});

const jobOrderForm = ref({
    po_number: '',
    items: [{ product_id: '', color: '', recipe_id: null, kilos: 0, unit_price: 0, description: '' }],
});

const openJobOrderModal = (attachment) => {
    jobOrderForm.value = {
        po_number: '',
        items: [{ product_id: '', color: '', recipe_id: null, kilos: 0, unit_price: 0, description: '' }],
    };
    jobOrderModal.value = { show: true, submitting: false, attachment, error: null };
};

const addJobOrderItem = () => {
    jobOrderForm.value.items.push({ product_id: '', color: '', recipe_id: null, kilos: 0, unit_price: 0, description: '' });
};

const removeJobOrderItem = (idx) => {
    jobOrderForm.value.items.splice(idx, 1);
};

const recipesForProduct = (productId) => {
    if (!productId) return [];

    const numId = Number(productId);
    if (isNaN(numId) || numId <= 0) return [];

    return props.recipes.filter(r => {
        const byForeignKey   = Number(r.product_id) === numId;
        const byRelationship = r.product && Number(r.product.id) === numId;
        return byForeignKey || byRelationship;
    });
};

const submitJobOrder = () => {
    jobOrderModal.value.error = null;
    jobOrderModal.value.submitting = true;
    router.post(route('eco.attachment.create-job-order', jobOrderModal.value.attachment.id), jobOrderForm.value, {
        onSuccess: () => {
            jobOrderModal.value.show = false;
        },
        onError: (errors) => {
            const messages = [];
            for (const key in errors) {
                if (Array.isArray(errors[key])) {
                    messages.push(...errors[key]);
                } else {
                    messages.push(errors[key]);
                }
            }
            jobOrderModal.value.error = messages.join(' ') || 'Failed to create job order.';
        },
        onFinish: () => { jobOrderModal.value.submitting = false; }
    });
};

onMounted(() => scrollToBottom());
</script>