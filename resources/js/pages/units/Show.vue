<script setup>
import { ref, computed } from 'vue'
// import { usePage } from '@inertiajs/vue3'
// import { router } from '@inertiajs/vue3'
import BackButton from '@/components/BackButton.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ReceiptModel from '@/components/ReceiptModel.vue'
import ButtonLink from '@/components/ButtonLink.vue'

const props = defineProps(['unit', 'project', 'organisation', 'transaction'])
console.log(props.unit);
// Capitalize first letter of each word
const ucwords = (str) => str?.replace(/\b\w/g, char => char.toUpperCase()) || ''

// Tabs
const activeTab = ref('all')

const filteredTransactions = computed(() => {
    if (activeTab.value === 'base') {
        return props.unit.transactions.filter(t => !t.gst)
    }
    if (activeTab.value === 'gst') {
        return props.unit.transactions.filter(t => t.gst)
    }
    return props.unit.transactions
})

const tabClass = (tab) => {
    return [
        'px-4 py-2 rounded border font-semibold',
        tab === activeTab.value
            ? 'bg-zinc-600 text-white'
            : 'bg-zinc-100 hover:bg-zinc-200 text-cyan-800'
    ]
}
function formatCurrency(amount) {
    console.log(amount);
    if (amount == null || amount === "") return "₹ 0.00";
    return "₹ " + Number(amount).toLocaleString("en-IN", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
</script>

<template>
    <AppLayout>

        <div class="py-5 lg:py-10">
            <div class="w-full lg:!w-[80%] mx-auto px-6 lg:px-0">
                <div class="mb-4 flex justify-between items-center">
                    <BackButton :prevRoute="route('units.index', [unit.project.organisation_id, unit.project.id])" />
                </div>

                <div class="bg-gray-100 mt-10 overflow-hidden shadow-md rounded-lg px-4 lg:px-8">
                    <div class="text-gray-800 py-8">
                        <div class="flex flex-col items-center justify-center py-4 border-gray-200 mb-8">
                            <img :src="unit.project.logo" :alt="`${unit.project.name}_logo`" class="max-h-24" />
                            <h6 class="m-0 text-primary font-bold text-center lg:text-4xl w-full mt-8">
                                Unit : {{ unit.unit_no }}
                            </h6>
                        </div>
                        <div class="flex justify-end gap-4 lg:mx-10">
                            <!-- <ButtonLink v-if="project?.id" icon="ic:twotone-add"
                            :route="route('transactions.create', project.id) + '?back=' + route('units.show', {
                                organisation: project.organisation_id,
                                project: project.id,
                                unit: unit.id
                            })">
                            Add Transaction
                        </ButtonLink> -->
                            <ButtonLink v-if="project?.id" icon="ic:twotone-add"
                                :route="route('transactions.create', project.id) + '?back=UnitsShow&organisation=' + project.organisation_id + '&project=' + project.id + '&unit=' + unit.id">
                                Add Transaction
                            </ButtonLink>

                        </div>
                        <!-- Unit Details -->
                        <div
                            class="grid grid-cols-1 lg:grid-cols-3 lg:mx-10 mt-8 border border-cyan-800 bg-zinc-200 text-black rounded-lg">
                            <div
                                class="flex flex-col gap-4 p-8 border-r border-b border-teal-800 text-center lg:text-left">
                                <p class="text-xl">Unit Type</p>
                                <p class="text-2xl">{{ ucwords(unit.type) }}</p>
                            </div>

                            <div
                                class="flex flex-col gap-4 p-8 border-r border-b border-teal-800 text-center lg:text-left">
                                <p class="text-xl">Status</p>
                                <p class="text-2xl">
                                    {{ unit.is_sold ? 'Sold' : 'Not Sold' }}
                                    <span v-if="unit.is_sold">&#128513;</span>
                                </p>
                            </div>

                            <div class="flex flex-col gap-4 p-8 border-b border-teal-800 text-center lg:text-left">
                                <p class="text-xl">Selling Price</p>
                                <p class="text-2xl">₹ {{ unit.formatted_total_amount }}</p>
                            </div>

                            <div
                                class="flex flex-col gap-4 p-8 border-r border-b border-teal-800 text-center lg:text-left">
                                <p class="text-xl">Customer Name</p>
                                <p class="text-2xl">{{ ucwords(unit.customer.name) }}</p>
                            </div>

                            <div
                                class="flex flex-col gap-4 p-8 border-r border-b border-teal-800 text-center lg:text-left">
                                <p class="text-xl">Customer Contact Number</p>
                                <p class="text-2xl">{{ unit.customer.mobile }}</p>
                            </div>

                            <div class="flex flex-col gap-4 p-8 border-b border-teal-800 text-center lg:text-left">
                                <p class="text-xl">Customer Email</p>
                                <p class="text-2xl break-words whitespace-normal">{{ unit.customer.email || 'N/A' }}</p>
                            </div>

                            <!-- Base Amount -->
                            <div class="flex flex-col gap-4 p-8 border-r border-teal-800 text-center lg:text-left">
                                <div>
                                    <p class="text-xl">Base Amount</p>
                                    <p><span class="text-green-600">Received</span> / <span
                                            class="text-red-500">Due</span>
                                    </p>
                                </div>
                                <p class="text-2xl">
                                    <span class="text-green-600">{{formatCurrency(unit.base_received_amount) }}</span> /
                                    <span class="text-red-500">{{ formatCurrency(unit.base_amount) }}</span>
                                </p>
                            </div>

                            <!-- GST Amount -->
                            <div class="flex flex-col gap-4 p-8 border-r border-teal-800 text-center lg:text-left">
                                <div>
                                    <p class="text-xl">GST Amount</p>
                                    <p><span class="text-green-600">Received</span> / <span
                                            class="text-red-500">Due</span>
                                    </p>
                                </div>
                                <p class="text-2xl">
                                    <span class="text-green-600">{{ formatCurrency(unit.formatted_gst_received_amount) }}</span> /
                                    <span class="text-red-500">₹ {{ unit.formatted_gst_due_amount }}</span>
                                </p>
                            </div>

                            <!-- Total Amount -->
                            <div class="flex flex-col gap-4 p-8 border-teal-800 text-center lg:text-left">
                                <div>
                                    <p class="text-xl">Total Amount</p>
                                    <p><span class="text-green-600">Received</span> / <span
                                            class="text-red-500">Due</span>
                                    </p>
                                </div>
                                <p class="text-2xl">
                                    <span class="text-green-600">₹ {{ unit.formatted_total_received_amount }}</span> /
                                    <span class="text-red-500">₹ {{ unit.formatted_total_due_amount }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Transactions Table -->
                        <div class="mt-8 lg:mx-10">
                            <details class="mt-4 rounded-lg bg-zinc-200 bg-opacity-10 border border-teal-800">
                                <summary class="cursor-pointer px-4 py-3 font-semibold text-cyan-800">
                                    View Transactions
                                </summary>

                                <!-- dasktop -->
                                <div class="lg:block hidden px-4 py-6">
                                    <!-- Tabs -->
                                    <div class="mb-4 flex gap-4">
                                        <button @click="activeTab = 'all'" :class="tabClass('all')">All
                                            Transactions</button>
                                        <button @click="activeTab = 'base'" :class="tabClass('base')">Base
                                            Transactions</button>
                                        <button @click="activeTab = 'gst'" :class="tabClass('gst')">GST
                                            Transactions</button>
                                    </div>

                                    <!-- Table -->
                                    <table class="w-full text-left border border-gray-200">
                                        <thead class="bg-zinc-100 text-center">
                                            <tr>
                                                <th class="px-4 py-2 border">#</th>
                                                <th class="px-4 py-2 border">Payment Date</th>
                                                <th class="px-4 py-2 border">
                                                    Receipt No</th>
                                                <th class="px-4 py-2 border">Amount</th>
                                                <th class="px-4 py-2 border">Instrument</th>
                                                <th class="px-4 py-2 border">Bank</th>
                                                <th class="px-4 py-2 border">Branch</th>
                                                <th class="px-4 py-2 border">Receipt Date</th>
                                                <th class="px-4 py-2 border">Receipt</th>
                                                <th class="px-4 py-2 border">Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(t, index) in filteredTransactions" :key="index"
                                                class="border-t text-center"
                                                :class="t.deleted_at ? ' line-through' : ''">

                                                <td class="px-4 py-2 border">{{ index + 1 }}</td>
                                                <td class="px-4 py-2 border">{{ t.payment_date }}</td>
                                                <td class="px-4 py-2 border">{{ t.receipt_number }}</td>
                                                <td class="px-4 py-2 border">{{ formatCurrency(t.transaction_amount) }}
                                                </td>
                                                <td class="px-4 py-2 border">{{ t.payment_type ?? '-' }}</td>
                                                <td class="px-4 py-2 border">{{ t.bank_name ?? '-' }}</td>
                                                <td class="px-4 py-2 border">{{ t.bank_branch ?? '-' }}</td>
                                                <td class="px-4 py-2 border">{{ t.receipt_date ?? '-' }}</td>
                                                <td class="px-4 py-2 border">
                                                    <span class="py-2 font-black">
                                                        <ReceiptModel :project="project" :transaction="t" size="sm" />
                                                    </span>
                                                    <!-- <span v-else>N/A</span> -->
                                                </td>
                                                <td class="px-4 py-2 border">{{ t.gst ? 'GST' : 'Base' }}</td>
                                            </tr>
                                            <tr v-if="filteredTransactions.length === 0">
                                                <td colspan="9" class="text-center py-4 text-gray-500">
                                                    No transactions found.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- mobile view -->
                                <div class="block lg:hidden px-4 py-6 space-y-4">
                                    <div class="mb-4 flex flex-wrap gap-2">
                                        <button @click="activeTab = 'all'" :class="tabClass('all')">All
                                            Transactions</button>
                                        <button @click="activeTab = 'base'" :class="tabClass('base')">Base
                                            Transactions</button>
                                        <button @click="activeTab = 'gst'" :class="tabClass('gst')">GST
                                            Transactions</button>
                                    </div>

                                    <div v-for="(t, index) in filteredTransactions" :key="index"
                                        class="bg-white shadow rounded border p-4 space-y-2">
                                        <div class="text-sm"><span class="font-semibold">#{{ index + 1 }}</span></div>
                                        <div class="text-sm"><span class="font-semibold">Payment Date:</span> {{
                                            t.payment_date }}</div>
                                        <div class="text-sm"><span class="font-semibold">Amount:</span> {{
                                            formatCurrency(t.transaction_amount) }}</div>
                                        <div class="text-sm"><span class="font-semibold">Instrument:</span> {{
                                            t.payment_type ?? '-' }}</div>
                                        <div class="text-sm"><span class="font-semibold">Bank:</span> {{ t.bank_name ??
                                            '-' }}</div>
                                        <div class="text-sm"><span class="font-semibold">Branch:</span> {{ t.bank_branch
                                            ?? '-' }}</div>
                                        <div class="text-sm"><span class="font-semibold">Receipt Date:</span> {{
                                            t.receipt_date ?? '-' }}</div>
                                        <div class="text-sm"><span class="font-semibold">Type:</span> {{ t.gst ? 'GST' :
                                            'Base' }}</div>
                                        <div class="text-sm"><span class="font-semibold">Receipt:</span>
                                            <span>
                                                <ReceiptModel :project="project" :transaction="t" size="sm" />
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="filteredTransactions.length === 0"
                                        class="text-center text-gray-500 py-4">
                                        No transactions found.
                                    </div>
                                </div>
                            </details>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
