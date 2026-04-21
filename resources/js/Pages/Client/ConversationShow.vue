<template>
    <Head :title="`Conversation: ${inquiry.product?.name || 'Bulk Inquiry'}`" />
    <AuthenticatedLayout>
        <div class="w-full h-[calc(100vh-64px)] flex flex-col overflow-hidden bg-white">

            <!-- ── Header ─────────────────────────────────────────── -->
            <div class="flex items-center gap-3 px-4 sm:px-6 py-3 bg-white border-b border-gray-100 flex-shrink-0">
                <Link :href="route('client.conversations')"
                    class="p-2 rounded-xl hover:bg-gray-100 transition text-gray-400 hover:text-gray-700 flex-shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div class="flex-1 min-w-0">
                    <h1 class="text-sm sm:text-base font-bold text-gray-900 leading-tight truncate">
                        {{ inquiry.product?.name || 'Multiple Products' }}
                    </h1>
                    <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mt-0.5">
                        {{ inquiry.product?.sku ? `SKU: ${inquiry.product.sku} · ` : '' }}{{ formatStatus(inquiry.status) }}
                    </p>
                </div>
                <button
                    @click="openPoModal"
                    :disabled="!hasAcceptedQuotation"
                    :class="hasAcceptedQuotation
                        ? 'bg-amber-400 hover:bg-amber-500 text-amber-900 cursor-pointer shadow-sm'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                    class="flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider transition flex-shrink-0">
                    <FileText class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">Send P.O.</span>
                    <span class="sm:hidden">P.O.</span>
                </button>
            </div>

            <!-- ── Mobile Tab Bar ───────────────────────────────────── -->
            <div class="flex lg:hidden border-b border-gray-100 bg-white flex-shrink-0">
                <button @click="mobilePanel = 'chat'"
                    :class="mobilePanel === 'chat' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                    class="flex-1 py-2.5 text-[11px] font-semibold uppercase tracking-wider transition">
                    Chat
                </button>
                <button @click="mobilePanel = 'proposals'"
                    :class="mobilePanel === 'proposals' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                    class="flex-1 py-2.5 text-[11px] font-semibold uppercase tracking-wider transition">
                    Proposals
                </button>
            </div>

            <!-- ── Main Layout ──────────────────────────────────────── -->
            <div class="flex flex-1 overflow-hidden">

                <!-- Left: Messages ──────────────────────────────────── -->
                <div :class="mobilePanel !== 'chat' ? 'hidden lg:flex' : 'flex'"
                    class="flex-1 flex-col overflow-hidden border-r border-gray-100 bg-white min-w-0">

                    <!-- Messages area -->
                    <div ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3 bg-gray-50/40">
                        <div v-for="msg in inquiry.messages" :key="msg.id"
                            class="flex flex-col"
                            :class="msg.sender_type === 'client' ? 'items-end' : 'items-start'">

                            <!-- System event pill -->
                            <div v-if="msg.is_system_event" class="w-full flex justify-center my-3">
                                <div class="bg-blue-50 border border-blue-100 px-4 py-1.5 rounded-full max-w-[85%]">
                                    <p class="text-[9px] font-semibold uppercase tracking-wide text-blue-500 text-center">
                                        {{ msg.message }}
                                    </p>
                                </div>
                            </div>

                            <!-- Regular message bubble -->
                            <div v-else
                                :class="msg.sender_type === 'client'
                                    ? 'bg-blue-600 text-white rounded-2xl rounded-tr-sm'
                                    : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-tl-sm shadow-sm'"
                                class="max-w-[80%] sm:max-w-[65%] px-4 py-3">

                                <p class="text-xs leading-relaxed whitespace-pre-wrap">{{ msg.message }}</p>

                                <!-- Attachments -->
                                <div v-if="msg.attachments && msg.attachments.length" class="mt-2 space-y-2">
                                    <div v-for="file in msg.attachments" :key="file.id">
                                        <!-- Image -->
                                        <div v-if="file.file_type.startsWith('image/')"
                                            class="rounded-xl overflow-hidden border border-black/10 relative">
                                            <img :src="getFullUrl(file.file_path)"
                                                class="max-w-full h-auto cursor-pointer"
                                                @click="openPreview(file)" />
                                            <div class="absolute bottom-2 right-2">
                                                <button v-if="!file.approved_by_client"
                                                    @click.stop="approveAttachment(file)"
                                                    class="px-2.5 py-1 bg-amber-400 text-amber-900 rounded-lg text-[9px] font-bold uppercase shadow-sm hover:bg-amber-500 transition">
                                                    Approve
                                                </button>
                                                <span v-else
                                                    class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-[9px] font-bold uppercase">
                                                    Approved ✓
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Non-image -->
                                        <div v-else
                                            class="flex items-center gap-2 px-3 py-2 bg-black/10 rounded-xl text-[10px] font-medium">
                                            <FileText class="h-3.5 w-3.5 flex-shrink-0" />
                                            <a :href="getFullUrl(file.file_path)" target="_blank"
                                                class="truncate hover:underline flex-1">{{ file.file_name }}</a>
                                            <button v-if="!file.approved_by_client"
                                                @click.stop="approveAttachment(file)"
                                                class="ml-1 px-2 py-1 bg-amber-400 text-amber-900 rounded-lg text-[8px] font-bold uppercase hover:bg-amber-500 transition flex-shrink-0">
                                                Approve
                                            </button>
                                            <span v-else
                                                class="ml-1 px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-[8px] font-bold uppercase flex-shrink-0">
                                                Approved ✓
                                            </span>
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
                        <div v-if="selectedFiles.length" class="flex gap-2 mb-3 overflow-x-auto pb-1">
                            <div v-for="(file, i) in selectedFiles" :key="i"
                                class="relative h-12 w-12 flex-shrink-0 bg-gray-100 rounded-xl border border-gray-200 overflow-hidden">
                                <img v-if="file.type.startsWith('image/')" :src="getFilePreview(file)"
                                    class="h-full w-full object-cover" />
                                <div v-else class="h-full w-full flex items-center justify-center">
                                    <FileText class="h-5 w-5 text-gray-400" />
                                </div>
                                <button @click="confirmRemoveFile(i)"
                                    class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 shadow">
                                    <X class="h-2.5 w-2.5" />
                                </button>
                            </div>
                        </div>
                        <!-- Input row -->
                        <div class="flex items-center gap-2 bg-gray-50 rounded-2xl px-3 py-2 border border-gray-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-500/10 transition">
                            <input v-model="newMessage" type="text"
                                placeholder="Type a message…"
                                class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-gray-800 placeholder:text-gray-400"
                                @keydown.enter.prevent="handleSend" />
                            <button type="button" @click="triggerFileUpload"
                                class="p-1.5 hover:bg-gray-200 rounded-lg transition text-gray-400 hover:text-gray-600">
                                <Paperclip class="h-4 w-4" />
                            </button>
                            <input ref="fileInput" type="file" class="hidden" multiple @change="onFilesSelected" />
                            <button type="button" @click="handleSend"
                                :disabled="sending || (!newMessage.trim() && !selectedFiles.length)"
                                class="p-2 bg-blue-600 text-white rounded-xl shadow-sm hover:bg-blue-700 disabled:opacity-40 transition">
                                <Send v-if="!sending" class="h-4 w-4" />
                                <Loader2 v-else class="h-4 w-4 animate-spin" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Proposals ────────────────────────────────── -->
                <div :class="mobilePanel !== 'proposals' ? 'hidden lg:block' : 'block'"
                    class="w-full lg:w-80 xl:w-96 flex-shrink-0 overflow-y-auto bg-gray-50 p-4 space-y-3">

                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 px-1 pt-1">Proposals</p>

                    <div v-for="quotation in quotations" :key="quotation.id"
                        class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">

                        <!-- Status bar -->
                        <div class="flex items-center justify-between px-4 py-2.5"
                            :class="quotation.status === 'accepted' ? 'bg-emerald-500'
                                : quotation.status === 'rejected' ? 'bg-red-500'
                                : 'bg-blue-600'">
                            <span class="font-mono text-[10px] font-semibold text-white">
                                {{ quotation.quotation_number }}
                            </span>
                            <span class="text-[9px] font-bold uppercase text-white/80 bg-black/15 px-2 py-0.5 rounded-full">
                                {{ quotation.status }}
                            </span>
                        </div>

                        <div class="p-3 space-y-2.5">
                            <!-- VAT badge -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[9px] font-semibold rounded-full px-2 py-0.5"
                                    :class="quotation.vat_type === 'inclusive'
                                        ? 'bg-blue-50 text-blue-600 border border-blue-100'
                                        : 'bg-gray-100 text-gray-500'">
                                    {{ quotation.vat_type === 'inclusive' ? 'VAT Incl. +12%' : 'VAT Excl.' }}
                                </span>
                            </div>

                            <!-- Fabric groups -->
                            <div v-for="(group, fabric) in groupItemsByFabric(quotation.items)" :key="fabric"
                                class="border border-gray-100 rounded-xl overflow-hidden">
                                <div class="bg-gray-50 px-3 py-2 flex items-center gap-1.5">
                                    <Package class="h-3 w-3 text-blue-500 flex-shrink-0" />
                                    <span class="text-[10px] font-semibold text-blue-700 uppercase truncate flex-1">
                                        {{ fabric }}
                                    </span>
                                    <span class="text-[9px] text-gray-400 font-medium">{{ group[0]?.kilos }}kg</span>
                                </div>
                                <div class="divide-y divide-gray-50">
                                    <div v-for="item in group" :key="item.id"
                                        class="px-3 py-2 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0 border"
                                                :style="colorDotStyle(item.color)"></div>
                                            <span class="text-[9px] font-semibold text-gray-600 uppercase">
                                                {{ item.color }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] font-bold text-blue-600">
                                                ₱{{ formatPrice(item.unit_price) }}/kg
                                                <span v-if="quotation.vat_type === 'inclusive'"
                                                    class="text-[8px] text-gray-400 ml-1">inc. VAT</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sent: Accept / Reject -->
                            <div v-if="quotation.status === 'sent'" class="flex gap-2 pt-1">
                                <button @click="openRejectModal(quotation)"
                                    class="flex-1 py-2 border border-red-200 text-red-500 rounded-xl text-[9px] font-semibold uppercase hover:bg-red-50 transition">
                                    Reject
                                </button>
                                <button @click="openAcceptModal(quotation)"
                                    class="flex-1 py-2 bg-blue-600 text-white rounded-xl text-[9px] font-semibold uppercase shadow-sm hover:bg-blue-700 transition">
                                    Accept
                                </button>
                            </div>

                            <!-- Accepted: Preview / Download -->
                            <div v-if="quotation.status === 'accepted'" class="pt-1">
                                <button @click="openQuotationPreview(quotation)"
                                    class="w-full py-2 bg-blue-50 text-blue-600 border border-blue-100 rounded-xl text-[9px] font-semibold uppercase flex items-center justify-center gap-2 hover:bg-blue-100 transition">
                                    <Download class="h-3 w-3" /> Preview & Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════
             SEND P.O. MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="poModal.show"
                class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="poModal.show = false">
                <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Send Purchase Order</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Attach your PO files below</p>
                        </div>
                        <button @click="poModal.show = false"
                            class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400 hover:text-gray-600">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <!-- Body -->
                    <form @submit.prevent="submitPo" class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                                PO Files (PDF / Images) *
                            </label>
                            <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition">
                                <Paperclip class="h-5 w-5 text-gray-400 mb-1" />
                                <span class="text-xs text-gray-400">Click to upload files</span>
                                <span class="text-[10px] text-gray-300 mt-0.5">PDF, JPG, PNG · Max 10MB each</span>
                                <input type="file" ref="poFileInput" @change="onPoFilesSelected" accept=".pdf,image/*" multiple class="hidden" />
                            </label>
                        </div>
                        <!-- File list -->
                        <div v-if="poModal.files.length > 0" class="flex gap-2 flex-wrap">
                            <div v-for="(file, idx) in poModal.files" :key="idx"
                                class="flex items-center gap-1.5 bg-gray-100 rounded-lg px-2.5 py-1.5 text-xs">
                                <FileText class="h-3 w-3 text-gray-500" />
                                <span class="truncate max-w-[120px] text-gray-700">{{ file.name }}</span>
                                <button type="button" @click="removePoFile(idx)"
                                    class="text-gray-400 hover:text-red-500 transition ml-0.5">
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Notes (Optional)</label>
                            <textarea v-model="poModal.notes" rows="2"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition resize-none"
                                placeholder="Add any remarks…"></textarea>
                        </div>
                        <button type="submit"
                            :disabled="poModal.submitting || poModal.files.length === 0"
                            class="w-full py-3 bg-amber-400 text-amber-900 rounded-xl font-bold text-xs uppercase tracking-wide flex justify-center items-center gap-2 hover:bg-amber-500 disabled:opacity-40 transition shadow-sm">
                            <Loader2 v-if="poModal.submitting" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                            Send Purchase Order
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             DIALOG MODAL (info / confirm / error)
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="dialog.show"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
                <div class="bg-white w-full max-w-xs rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-6 text-center">
                        <div class="h-14 w-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                            :class="dialog.type === 'error' ? 'bg-red-100'
                                : dialog.type === 'confirm' ? 'bg-blue-100'
                                : 'bg-emerald-100'">
                            <X v-if="dialog.type === 'error'" class="h-6 w-6 text-red-500" />
                            <Send v-else-if="dialog.type === 'confirm'" class="h-6 w-6 text-blue-600" />
                            <Loader2 v-else class="h-6 w-6 text-emerald-600 animate-spin" />
                        </div>
                        <h3 class="text-sm font-bold text-gray-900 mb-1">{{ dialog.title }}</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">{{ dialog.message }}</p>
                    </div>
                    <div class="flex border-t border-gray-100">
                        <button v-if="dialog.type === 'confirm'"
                            @click="dialog.show = false"
                            class="flex-1 py-3.5 text-xs font-semibold text-gray-400 hover:bg-gray-50 transition border-r border-gray-100">
                            Cancel
                        </button>
                        <button @click="handleDialogAction"
                            class="flex-1 py-3.5 text-xs font-bold uppercase"
                            :class="dialog.type === 'error' ? 'text-red-500 hover:bg-red-50' : 'text-blue-600 hover:bg-blue-50'">
                            {{ dialog.type === 'confirm' ? 'Confirm' : 'Got it' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             FILE PREVIEW MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="previewModal.show"
                class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/95 backdrop-blur-md"
                @click.self="previewModal.show = false">
                <button @click="previewModal.show = false"
                    class="absolute top-5 right-5 p-2 bg-white/10 hover:bg-white/20 rounded-xl transition z-[80]">
                    <X class="h-5 w-5 text-white" />
                </button>
                <div class="w-full max-w-5xl h-[88vh] flex flex-col gap-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/60 px-1">
                        {{ previewModal.title }}
                    </p>
                    <div class="flex-1 bg-black/40 rounded-2xl overflow-hidden border border-white/10 flex items-center justify-center">
                        <div v-if="previewModal.activeFile" class="w-full h-full flex items-center justify-center p-4">
                            <img v-if="previewModal.activeFile.file_type?.startsWith('image/') || previewModal.activeFile.type?.startsWith('image/')"
                                :src="previewModal.activeFile.url || getFilePreview(previewModal.activeFile)"
                                class="max-w-full max-h-full object-contain rounded-xl shadow-2xl" />
                            <div v-else class="bg-white/10 p-10 rounded-2xl flex flex-col items-center gap-4">
                                <FileText class="h-16 w-16 text-white/40" />
                                <p class="text-sm font-semibold text-white/70 uppercase tracking-wide">
                                    {{ previewModal.activeFile.file_name || previewModal.activeFile.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Thumbnails -->
                    <div v-if="previewModal.files.length > 1"
                        class="h-20 flex gap-2 overflow-x-auto py-1 px-1 scrollbar-hide">
                        <div v-for="(file, idx) in previewModal.files" :key="idx"
                            @click="previewModal.activeFile = file"
                            :class="previewModal.activeFile === file
                                ? 'ring-2 ring-blue-400 ring-offset-1 ring-offset-black/80'
                                : 'opacity-50 hover:opacity-70'"
                            class="h-full aspect-square flex-shrink-0 rounded-xl overflow-hidden cursor-pointer transition">
                            <img v-if="file.file_type?.startsWith('image/') || file.type?.startsWith('image/')"
                                :src="file.url || getFilePreview(file)"
                                class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full bg-white/10 flex items-center justify-center">
                                <FileText class="h-6 w-6 text-white/50" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             ACCEPT QUOTATION MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="acceptModal.show"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="acceptModal.show = false">
                <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Accept Quotation</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Review and confirm your acceptance</p>
                        </div>
                        <button @click="acceptModal.show = false"
                            class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <!-- Body -->
                    <form @submit.prevent="submitAccept" class="p-5 space-y-4 overflow-y-auto flex-1">
                        <!-- Summary -->
                        <div v-if="acceptModal.quotation"
                            class="bg-gray-50 rounded-xl border border-gray-100 p-4 max-h-60 overflow-y-auto">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Summary</p>
                            <div class="space-y-2">
                                <div v-for="item in acceptModal.quotation.items" :key="item.id"
                                    class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full border flex-shrink-0"
                                            :style="colorDotStyle(item.color)"></div>
                                        <span class="text-xs text-gray-700 uppercase font-medium">{{ item.fabric }}</span>
                                        <span class="text-[9px] text-gray-400">({{ item.color }})</span>
                                    </div>
                                    <span class="text-xs font-bold text-blue-600">₱{{ formatPrice(item.unit_price) }}/kg</span>
                                </div>
                            </div>
                        </div>
                        <!-- Notes -->
                        <textarea v-model="acceptModal.notes" rows="2"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition resize-none"
                            placeholder="Notes (optional)"></textarea>
                        <!-- Attachments -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Attachments (Optional)</label>
                                <button type="button" @click="$refs.acceptFileInput.click()"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                                    + Add Files
                                </button>
                            </div>
                            <div v-if="acceptModal.files.length" class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                                <div v-for="(file, i) in acceptModal.files" :key="i"
                                    class="relative group h-14 w-14 flex-shrink-0 bg-gray-100 rounded-xl overflow-hidden border border-gray-200">
                                    <img v-if="file.type.startsWith('image/')" :src="getFilePreview(file)"
                                        class="h-full w-full object-cover" />
                                    <div v-else class="h-full w-full flex items-center justify-center">
                                        <FileText class="h-5 w-5 text-gray-400" />
                                    </div>
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-1.5 transition">
                                        <button type="button" @click="previewAcceptFile(file)"
                                            class="p-1.5 bg-white/20 rounded-lg">
                                            <Eye class="h-3 w-3 text-white" />
                                        </button>
                                        <button type="button" @click="confirmRemoveAcceptFile(i)"
                                            class="p-1.5 bg-red-500 rounded-lg">
                                            <Trash2 class="h-3 w-3 text-white" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input ref="acceptFileInput" type="file" class="hidden" multiple @change="onAcceptFilesSelected" />
                        <!-- Submit -->
                        <button type="submit" :disabled="acceptModal.submitting"
                            class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold text-xs uppercase tracking-wide flex justify-center items-center gap-2 hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            <Loader2 v-if="acceptModal.submitting" class="h-4 w-4 animate-spin" />
                            Confirm Acceptance
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             REJECT QUOTATION MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="rejectModal.show"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="rejectModal.show = false">
                <div class="bg-white w-full sm:max-w-sm rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Reject Quotation</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Please provide a reason</p>
                        </div>
                        <button @click="rejectModal.show = false"
                            class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <form @submit.prevent="submitReject" class="p-5 space-y-4">
                        <textarea v-model="rejectModal.reason" rows="3" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-400 transition resize-none"
                            placeholder="Explain why you're rejecting this quotation…"></textarea>
                        <div class="flex gap-3">
                            <button type="button" @click="rejectModal.show = false"
                                class="flex-1 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl text-xs font-semibold uppercase hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" :disabled="rejectModal.submitting"
                                class="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-xs font-bold uppercase hover:bg-red-600 disabled:opacity-50 transition">
                                Confirm Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ════════════════════════════════════════════
             QUOTATION PREVIEW & DOWNLOAD MODAL
             ════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="quotationPreviewModal.show"
                class="fixed inset-0 z-[120] flex items-center justify-center p-2 sm:p-4 bg-black/70 backdrop-blur-sm"
                @click.self="quotationPreviewModal.show = false">
                <div class="bg-white w-full max-w-3xl max-h-[95vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-gray-100 flex-shrink-0">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Quotation Preview</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ quotationPreviewModal.quotation?.quotation_number }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="downloadQuotationPDF(quotationPreviewModal.quotation)"
                                :disabled="quotationPreviewModal.downloading"
                                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold uppercase hover:bg-blue-700 transition shadow-sm disabled:opacity-60">
                                <Loader2 v-if="quotationPreviewModal.downloading" class="h-3.5 w-3.5 animate-spin" />
                                <Download v-else class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">Download PDF</span>
                            </button>
                            <button @click="quotationPreviewModal.show = false"
                                class="p-2 hover:bg-gray-100 rounded-xl transition text-gray-400">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Document preview body -->
                    <div class="flex-1 overflow-y-auto bg-gray-100 p-4 sm:p-6">
                        <div v-if="quotationPreviewModal.quotation" id="quotation-doc"
                            class="bg-white shadow-lg mx-auto"
                            style="max-width:680px; padding:40px 48px; font-family:'Times New Roman',Times,serif;">

                            <!-- Company Header -->
                            <div style="display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:10px;">
                                <img src="/images/applogo.png" alt="Monti Textile Logo"
                                    style="width:52px;height:52px;flex-shrink:0;object-fit:contain;" />
                                <div style="text-align:center;">
                                    <div style="font-size:22px;font-style:italic;font-weight:700;color:#1a1a2e;letter-spacing:0.5px;line-height:1.1;">
                                        Monti Textile Manufacturing Corp.
                                    </div>
                                    <div style="font-size:9px;font-weight:700;font-family:Arial,sans-serif;letter-spacing:2px;color:#333;margin-top:3px;">
                                        MANUFACTURER OF QUALITY FABRICS
                                    </div>
                                    <div style="font-size:8.5px;font-family:Arial,sans-serif;color:#555;margin-top:1px;">
                                        KM. 22, ANABU I, IMUS CAVITE
                                    </div>
                                </div>
                            </div>

                            <hr style="border:none;border-top:1.5px solid #ccc;margin:14px 0;" />

                            <!-- Title block -->
                            <div style="margin-bottom:10px;">
                                <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:4px;">
                                    <div style="font-size:10px;font-weight:700;font-family:Arial,sans-serif;color:#111;">
                                        Quotation &mdash; {{ quotationPreviewModal.quotation.quotation_number }}
                                    </div>
                                    <div style="font-size:9px;font-family:Arial,sans-serif;color:#555;">
                                        Prepared: {{ formatDocDate(quotationPreviewModal.quotation.created_at) }}
                                    </div>
                                </div>
                                <div style="font-size:8px;font-family:Arial,sans-serif;color:#444;margin-top:5px;line-height:1.5;">
                                    All prices listed are <strong>{{ quotationPreviewModal.quotation.vat_type === 'inclusive' ? 'VAT-inclusive' : 'VAT-exclusive' }}</strong>
                                    and quoted <strong>per kilogram (kg)</strong>.
                                    Please note that prices are subject to change in accordance with fluctuations in yarn market costs.
                                </div>
                            </div>

                            <!-- Fabric Pricelist Table -->
                            <div style="font-size:10px;font-style:italic;font-weight:700;font-family:Arial,sans-serif;margin-bottom:6px;color:#1a1a2e;">
                                Fabric Pricelist
                            </div>
                            <table style="width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:9px;margin-bottom:16px;">
                                <thead>
                                    <tr style="background:#f5f5f5;">
                                        <th style="border:1px solid #ccc;padding:6px 10px;text-align:left;font-weight:700;width:45%;">Product Name</th>
                                        <th style="border:1px solid #ccc;padding:6px 10px;text-align:center;font-weight:700;width:25%;">Color</th>
                                        <th style="border:1px solid #ccc;padding:6px 10px;text-align:center;font-weight:700;width:15%;">MOQ (kg)</th>
                                        <th style="border:1px solid #ccc;padding:6px 10px;text-align:right;font-weight:700;width:15%;">Price/kg</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="(group, fabric) in groupItemsByFabric(quotationPreviewModal.quotation.items)" :key="fabric">
                                        <tr v-for="(item, idx) in group" :key="item.id"
                                            :style="idx % 2 === 0 ? 'background:#fff;' : 'background:#fafafa;'">
                                            <td style="border:1px solid #ddd;padding:5px 10px;">{{ idx === 0 ? fabric : '' }}</td>
                                            <td style="border:1px solid #ddd;padding:5px 10px;text-align:center;">{{ item.color }}</td>
                                            <td style="border:1px solid #ddd;padding:5px 10px;text-align:center;">{{ item.kilos }}</td>
                                            <td style="border:1px solid #ddd;padding:5px 10px;text-align:right;">&#8369;{{ formatPrice(item.unit_price) }}</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            <!-- VAT note -->
                            <div v-if="quotationPreviewModal.quotation.vat_type === 'inclusive'"
                                style="font-size:7.5px;font-family:Arial,sans-serif;color:#888;margin-bottom:40px;font-style:italic;">
                                * All prices include 12% Value Added Tax (VAT).
                            </div>
                            <div v-else style="margin-bottom:40px;"></div>

                            <!-- Signature lines -->
                            <div style="display:flex;justify-content:space-between;margin-top:20px;">
                                <div style="width:45%;">
                                    <div style="border-top:1.5px solid #333;padding-top:6px;">
                                        <div style="font-size:9px;font-family:Arial,sans-serif;font-weight:700;color:#111;">Thomas Dominick C. Ong</div>
                                        <div style="font-size:8px;font-family:Arial,sans-serif;color:#555;margin-top:1px;">QA Lead</div>
                                    </div>
                                </div>
                                <div style="width:40%;">
                                    <div style="border-top:1.5px solid #333;padding-top:6px;">
                                        <div style="font-size:9px;font-family:Arial,sans-serif;color:#555;">Received By</div>
                                    </div>
                                </div>
                            </div>
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
import { ArrowLeft, Paperclip, Send, Loader2, FileText, X, Eye, Trash2, Layers, Package, Download } from 'lucide-vue-next';

const props = defineProps({ inquiry: Object, quotations: Array });
const messagesContainer = ref(null);
const newMessage = ref('');
const sending = ref(false);
const fileInput = ref(null);
const selectedFiles = ref([]);

// Mobile panel toggle
const mobilePanel = ref('chat');

// PO Modal state (multiple files)
const poModal = ref({ show: false, files: [], notes: '', submitting: false });
const poFileInput = ref(null);

const rejectModal = ref({ show: false, quotation: null, reason: '', requestNew: false, submitting: false });
const acceptModal = ref({ show: false, quotation: null, notes: '', files: [], submitting: false });
const acceptFileInput = ref(null);
const previewModal = ref({ show: false, title: '', files: [], activeFile: null });

// Quotation preview/download modal
const quotationPreviewModal = ref({ show: false, quotation: null, downloading: false });

// Dialog Modal State
const dialog = ref({ show: false, type: 'info', title: '', message: '', onConfirm: null });
const showDialog = (type, title, message, onConfirm = null) => { dialog.value = { show: true, type, title, message, onConfirm }; };
const handleDialogAction = () => { if (dialog.value.onConfirm) dialog.value.onConfirm(); dialog.value.show = false; };

// Computed: check if any quotation is accepted
const hasAcceptedQuotation = computed(() => {
    return props.quotations.some(q => q.status === 'accepted');
});

const scrollToBottom = () => { nextTick(() => { if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight; }); };

const viewQuotationAttachments = (q) => {
    const acceptedMsg = props.inquiry.messages.find(m => m.is_system_event && m.message.includes(q.quotation_number) && m.message.includes('ACCEPTED') && m.attachments?.length > 0);
    if (acceptedMsg) { previewModal.value = { show: true, title: q.quotation_number, files: acceptedMsg.attachments, activeFile: acceptedMsg.attachments[0] }; }
    else { showDialog('error', 'No Files', 'Could not find attachments for this quotation.'); }
};

const triggerFileUpload = () => fileInput.value.click();
const onFilesSelected = (e) => { selectedFiles.value.push(...Array.from(e.target.files)); e.target.value = ''; };
const getFilePreview = (file) => URL.createObjectURL(file);

// Helper to get full storage URL
const getFullUrl = (path) => {
    if (!path) return '#';
    if (path.startsWith('http')) return path;
    const cleanPath = path.replace(/^\/?storage\//, '');
    return `/storage/${cleanPath}`;
};

const openPreview = (file) => {
    previewModal.value = {
        show: true,
        title: file.file_name,
        files: [file],
        activeFile: file
    };
};

// Approve an attachment (client side)
const approveAttachment = (file) => {
    router.post(route('client.conversation.attachment.approve', file.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['inquiry'] });
            showDialog('success', 'Approved', 'Attachment has been marked as approved.');
        },
        onError: (errors) => {
            showDialog('error', 'Error', errors.message || 'Failed to approve attachment.');
        }
    });
};

// PO Modal functions (multiple files)
const openPoModal = () => {
    if (!hasAcceptedQuotation.value) return;
    poModal.value = { show: true, files: [], notes: '', submitting: false };
    if (poFileInput.value) poFileInput.value.value = '';
};

const onPoFilesSelected = (e) => {
    const files = Array.from(e.target.files);
    if (files.length === 0) return;
    poModal.value.files.push(...files);
    e.target.value = '';
};

const removePoFile = (index) => {
    poModal.value.files.splice(index, 1);
};

const submitPo = async () => {
    if (poModal.value.files.length === 0) {
        showDialog('error', 'No Files', 'Please select at least one file.');
        return;
    }
    poModal.value.submitting = true;
    const formData = new FormData();
    poModal.value.files.forEach((file) => {
        formData.append('po_files[]', file);
    });
    formData.append('notes', poModal.value.notes || '');

    router.post(route('client.conversation.send-po', props.inquiry.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            poModal.value.show = false;
            showDialog('success', 'PO Sent', 'Your Purchase Order(s) have been sent to our team.');
            scrollToBottom();
        },
        onError: (errors) => {
            showDialog('error', 'Upload Failed', errors.message || 'Failed to send Purchase Order.');
        },
        onFinish: () => { poModal.value.submitting = false; }
    });
};

// Confirmed delete for Main Input Selection
const confirmRemoveFile = (index) => {
    showDialog('confirm', 'Remove Attachment', 'Are you sure you want to remove this file from your message?', () => {
        selectedFiles.value.splice(index, 1);
    });
};

const handleSend = async () => {
    if (!newMessage.value.trim() && !selectedFiles.value.length) return;
    sending.value = true;
    const formData = new FormData();
    formData.append('message', newMessage.value || '');
    selectedFiles.value.forEach((file, index) => formData.append(`files[${index}]`, file));
    router.post(route('client.conversation.message', props.inquiry.id), formData, {
        forceFormData: true,
        onSuccess: () => { newMessage.value = ''; selectedFiles.value = []; scrollToBottom(); },
        onFinish: () => sending.value = false
    });
};

const openAcceptModal = (quotation) => { acceptModal.value = { show: true, quotation, notes: '', files: [], submitting: false }; };
const onAcceptFilesSelected = (e) => { acceptModal.value.files.push(...Array.from(e.target.files)); e.target.value = ''; };
const previewAcceptFile = (file) => { previewModal.value = { show: true, title: "Selected File", files: acceptModal.value.files, activeFile: file }; };

const confirmRemoveAcceptFile = (index) => {
    showDialog('confirm', 'Remove File', 'Do you want to remove this attachment from the quotation acceptance?', () => {
        acceptModal.value.files.splice(index, 1);
    });
};

const submitAccept = () => {
    showDialog('confirm', 'Accept Quotation', 'Do you confirm that you accept this quotation? You will be able to send your Purchase Order afterwards.', () => {
        acceptModal.value.submitting = true;
        const formData = new FormData();
        formData.append('notes', acceptModal.value.notes);
        acceptModal.value.files.forEach((file, index) => formData.append(`files[${index}]`, file));
        router.post(route('client.quotation.accept', acceptModal.value.quotation.id), formData, {
            forceFormData: true,
            onSuccess: () => { acceptModal.value.show = false; },
            onFinish: () => { acceptModal.value.submitting = false; }
        });
    });
};

const openRejectModal = (quotation) => { rejectModal.value = { show: true, quotation, reason: '', requestNew: false, submitting: false }; };
const submitReject = () => {
    rejectModal.value.submitting = true;
    router.post(route('client.quotation.reject', rejectModal.value.quotation.id), { reason: rejectModal.value.reason }, {
        onSuccess: () => { rejectModal.value.show = false; },
        onFinish: () => { rejectModal.value.submitting = false; }
    });
};

// =====================================================
// QUOTATION PREVIEW & PDF DOWNLOAD
// =====================================================

const openQuotationPreview = (quotation) => {
    quotationPreviewModal.value = { show: true, quotation, downloading: false };
};

const formatDocDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
};

const loadJsPDF = () => new Promise((resolve, reject) => {
    if (window.jspdf) {
        resolve(window.jspdf);
        return;
    }
    const s1 = document.createElement('script');
    s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    s1.onload = () => {
        const s2 = document.createElement('script');
        s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js';
        s2.onload = () => resolve(window.jspdf);
        s2.onerror = () => reject(new Error('Failed to load jspdf-autotable'));
        document.head.appendChild(s2);
    };
    s1.onerror = () => reject(new Error('Failed to load jsPDF'));
    document.head.appendChild(s1);
});

const fetchLogoAsBase64 = () => new Promise((resolve) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
        try {
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            canvas.getContext('2d').drawImage(img, 0, 0);
            resolve(canvas.toDataURL('image/png'));
        } catch {
            resolve(null);
        }
    };
    img.onerror = () => resolve(null);
    img.src = '/images/applogo.png?' + Date.now();
});

const downloadQuotationPDF = async (quotation) => {
    if (!quotation) return;
    quotationPreviewModal.value.downloading = true;

    try {
        const [{ jsPDF }, logoDataUrl] = await Promise.all([
            loadJsPDF(),
            fetchLogoAsBase64(),
        ]);

        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

        const pageW  = doc.internal.pageSize.getWidth();
        const pageH  = doc.internal.pageSize.getHeight();
        const marginL  = 20;
        const marginR  = 20;
        const contentW = pageW - marginL - marginR;
        let y = 16;

        const logoSize   = 18;
        const logoGap    = 5;
        const textBlockW = 100;
        const groupW     = logoSize + logoGap + textBlockW;
        const groupX     = (pageW - groupW) / 2;
        const logoX      = groupX;
        const textCX     = groupX + logoSize + logoGap + textBlockW / 2;

        if (logoDataUrl) {
            doc.addImage(logoDataUrl, 'PNG', logoX, y, logoSize, logoSize);
        } else {
            const cx = logoX + logoSize / 2;
            const cy = y + logoSize / 2;
            const r  = logoSize / 2;
            doc.setFillColor(224, 123, 154);
            doc.circle(cx, cy, r, 'F');
            doc.setFillColor(255, 255, 255);
            const outerR = r * 0.55;
            const innerR = r * 0.22;
            const pts    = 5;
            const coords = [];
            for (let i = 0; i < pts * 2; i++) {
                const angle = (Math.PI / pts) * i - Math.PI / 2;
                const rd    = i % 2 === 0 ? outerR : innerR;
                coords.push([cx + rd * Math.cos(angle), cy + rd * Math.sin(angle)]);
            }
            const relLines = coords.slice(1).map((p, i) => [
                p[0] - coords[i][0],
                p[1] - coords[i][1],
            ]);
            doc.lines(relLines, coords[0][0], coords[0][1], [1, 1], 'F', true);
        }

        doc.setFont('times', 'bolditalic');
        doc.setFontSize(17);
        doc.setTextColor(26, 26, 46);
        doc.text('Monti Textile Manufacturing Corp.', textCX, y + 6.5, { align: 'center' });

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(51, 51, 51);
        doc.text('MANUFACTURER OF QUALITY FABRICS', textCX, y + 11.5, { align: 'center' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(85, 85, 85);
        doc.text('KM. 22, ANABU I, IMUS CAVITE', textCX, y + 15.5, { align: 'center' });

        y += logoSize + 5;

        doc.setDrawColor(180, 180, 180);
        doc.setLineWidth(0.5);
        doc.line(marginL, y, pageW - marginR, y);
        y += 6;

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(17, 17, 17);
        doc.text(`Quotation \u2014 ${quotation.quotation_number}`, marginL, y);

        const preparedDate = formatDocDate(quotation.created_at);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(85, 85, 85);
        doc.text(`Prepared: ${preparedDate}`, pageW - marginR, y, { align: 'right' });
        y += 5;

        const vatText = quotation.vat_type === 'inclusive'
            ? 'All prices listed are VAT-inclusive and quoted per kilogram (kg).'
            : 'All prices listed are VAT-exclusive and quoted per kilogram (kg).';
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(68, 68, 68);
        const vatLines = doc.splitTextToSize(vatText, contentW);
        doc.text(vatLines, marginL, y);
        y += vatLines.length * 3.5;

        const changeNote = 'Please note that prices are subject to change in accordance with fluctuations in yarn market costs.';
        const changeLines = doc.splitTextToSize(changeNote, contentW);
        doc.text(changeLines, marginL, y);
        y += changeLines.length * 3.5 + 5;

        doc.setFont('helvetica', 'bolditalic');
        doc.setFontSize(9.5);
        doc.setTextColor(26, 26, 46);
        doc.text('Fabric Pricelist', marginL, y);
        y += 4;

        const groups    = groupItemsByFabric(quotation.items);
        const tableBody = [];

        Object.entries(groups).forEach(([fabric, items]) => {
            items.forEach((item, idx) => {
                tableBody.push([
                    idx === 0 ? fabric : '',
                    item.color,
                    `${parseFloat(item.kilos).toFixed(2)}`,
                    `PHP ${formatPrice(item.unit_price)}`,
                ]);
            });
        });

        doc.autoTable({
            startY: y,
            head: [['Product Name', 'Color', 'MOQ (kg)', 'Price/kg']],
            body: tableBody,
            theme: 'grid',
            headStyles: {
                fillColor: [240, 240, 240],
                textColor: [0, 0, 0],
                fontStyle: 'bold',
                fontSize: 8,
                halign: 'center',
            },
            bodyStyles: {
                fontSize: 8,
                textColor: [30, 30, 30],
            },
            alternateRowStyles: { fillColor: [250, 250, 250] },
            margin: { left: marginL, right: marginR },
            columnStyles: {
                0: { cellWidth: 78, halign: 'left' },
                1: { cellWidth: 46, halign: 'center' },
                2: { cellWidth: 22, halign: 'center' },
                3: { cellWidth: 24, halign: 'right' },
            },
        });

        y = doc.lastAutoTable.finalY + 6;

        if (quotation.vat_type === 'inclusive') {
            doc.setFont('helvetica', 'italic');
            doc.setFontSize(7);
            doc.setTextColor(150, 150, 150);
            doc.text('* All prices include 12% Value Added Tax (VAT).', marginL, y);
            y += 5;
        }

        const sigY = Math.max(y + 30, Math.min(265, pageH - 25));

        doc.setDrawColor(50, 50, 50);
        doc.setLineWidth(0.6);

        const leftSigX = marginL;
        doc.line(leftSigX, sigY, leftSigX + 72, sigY);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(17, 17, 17);
        doc.text('Thomas Dominick C. Ong', leftSigX, sigY + 4.5);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(85, 85, 85);
        doc.text('QA Lead', leftSigX, sigY + 8.5);

        const rightSigX = pageW - marginR - 68;
        doc.line(rightSigX, sigY, rightSigX + 68, sigY);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(85, 85, 85);
        doc.text('Received By', rightSigX, sigY + 4.5);

        doc.save(`Quotation-${quotation.quotation_number}.pdf`);

    } catch (err) {
        console.error('PDF generation failed:', err);
        showDialog('error', 'Download Failed', 'Could not generate the PDF. Please try again.');
    } finally {
        quotationPreviewModal.value.downloading = false;
    }
};

// =====================================================
// HELPERS
// =====================================================

const formatPrice  = (v) => Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 });
const formatTime   = (d) => new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
const formatStatus = (s) => s ? s.replace(/_/g, ' ').toUpperCase() : 'OPEN';
const openUrl      = (url) => window.open(url, '_blank');

const groupItemsByFabric = (items) =>
    (items || []).reduce((acc, item) => {
        (acc[item.fabric] = acc[item.fabric] || []).push(item);
        return acc;
    }, {});

const colorDotStyle = (color) => {
    if (color === 'White')        return 'background-color:#f5f5f5; border-color:#d1d5db';
    if (color === 'Light Colors') return 'background-color:#fde68a; border-color:#fbbf24';
    if (color === 'Dark Colors')  return 'background-color:#374151; border-color:#1f2937';
    return 'background-color:#9ca3af; border-color:#6b7280';
};

watch(() => props.inquiry.messages, () => scrollToBottom(), { deep: true });
onMounted(() => scrollToBottom());
</script>