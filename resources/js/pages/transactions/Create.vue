<script setup>
import { computed, onMounted, reactive, toRaw, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import { useForm } from '@inertiajs/vue3';
import FormInput from '@/components/FormInput.vue';
import { useQueryParam } from '@/composables/useQueryParam';

const toast = new ToastMagic();
const props = defineProps(['units', 'project']);
console.log(toRaw(props.units));
const back = useQueryParam('back', '/', true);
// const back = useQueryParam('back', '/')
const organisationId = useQueryParam('organisation');
const projectId = useQueryParam('project');
const unitId = useQueryParam('unit');
console.log('back Query:', back.value);

// Reactive state
const state = reactive({
    selectedUnit: null,
    unitDefined: false
});

// Filter only sold units
const soldUnits = computed(() => props.units.filter(u => u.is_sold));

// Transaction form
const form = useForm({
    payment_date: '',
    receipt_date: '',
    transaction_amount: '',
    gst: false,
    payment_type: '',
    payment_reference: '',
    bank_name: '',
    bank_branch: ''
});


function submitTransaction()
{
    if (!state.selectedUnit?.id) return;

    const baseDueAmount = parseFloat(state.selectedUnit.base_amount) - parseFloat(state.selectedUnit.base_received_amount);
    const gstDueAmount = parseFloat(state.selectedUnit.gst_amount) - parseFloat(state.selectedUnit.gst_received_amount);
    const enteredAmount = parseFloat(form.transaction_amount ?? 0);

    if (!form.transaction_amount || enteredAmount <= 0)
    {
        toast.error('Transaction amount is required and must be greater than zero.');
        return;
    }

    if (form.gst === true)
    {
        if (enteredAmount > gstDueAmount)
        {
            toast.error('Transaction amount should be less than the GST due amount.');
            return;
        }
    } else if (form.gst === false)
    {
        if (enteredAmount > baseDueAmount)
        {
            toast.error('Transaction amount exceeds the base amount due.');
            return;
        }
    }

    form.post(route('transactions.store', { unit: state.selectedUnit.id }), {
        onSuccess: () =>
        {
            toast.success('Transaction submitted successfully!');
        },
        onError: () =>
        {
            toast.error('Failed to submit transaction.');
        }
    });
}

onMounted(() =>
{
    if (unitId.value)
    {
        const foundUnit = props.units.find(u => u.id === Number(unitId.value));
        if (foundUnit)
        {
            state.selectedUnit = foundUnit;
            state.unitDefined = true;
        }
    }
});

watch(() => form.payment_type, (newVal) =>
{
    if (newVal === 'cash')
    {
        form.payment_reference = null;
    }
});

function formatAmount(value)
{
    if (!value) return '0.00';
    return Number(value).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>

<template>
    <AppLayout>
        <!-- <pre>{{units}}</pre> -->
        <div class="py-5 lg:py-10 mt-10 lg:mt-0">
            <div class="lg:w-[80%] mx-auto px-6 lg:px-0">
                <div class="flex justify-between  w-full mx-auto mb-4">
                    <BackButton :prevRoute="route(back, {
                        organisation: organisationId,
                        project: projectId,
                        unit: unitId
                    })" />
                </div>

                <div class="bg-gray-100 overflow-hidden lg:shadow-md rounded-lg p-6 border-t-4 border-primary mt-10">
                    <h2 class="text-2xl font-bold text-center text-primary mt-5 mb-6">
                        Add Transaction for Sold Unit
                    </h2>
                    <!-- Sold Unit Dropdown (Grouped) -->
                    <div class="mb-6">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Select Sold Unit:</label>
                        <select v-model="state.selectedUnit" @change="state.unitDefined = true"
                                class="w-full p-2 border rounded bg-white rounded-md">
                            <option disabled value="">Select a sold unit</option>

                            <!-- Optional grouping -->
                            <optgroup label="Available (Sold)">
                                <!-- <option v-for="unit in props.units.filter(u => u.is_sold)" :key="'sold-' + unit.id"
                                    :value="unit">
                                    Unit No: {{ unit.unit_no }}
                                    ({{ unit.customer?.name || 'No Customer' }})
                                </option> -->
                                <option v-for="unit in props.units.filter(u => u.is_sold)" :key="'sold-' + unit.id"
                                        :value="unit" :selected="unit.id === Number(unitId)">
                                    Unit No: {{ unit.unit_no }} ({{ unit.customer?.name || 'No Customer' }})
                                </option>
                            </optgroup>

                            <optgroup label="Unavailable (Not Sold)">
                                <option v-for="unit in props.units.filter(u => !u.is_sold)" :key="'unsold-' + unit.id"
                                        :value="unit" disabled>
                                    Unit No: {{ unit.unit_no }}
                                </option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Message if no sold units -->
                    <div v-if="soldUnits.length === 0" class="text-center text-red-500 mt-10">
                        No sold units found to add a transaction.
                    </div>

                    <!-- Transaction Form -->
                    <div v-if="state.unitDefined && state.selectedUnit" class="mt-8">
                        <!-- <pre>{{ units }}</pre> -->
                        <div v-if="state.selectedUnit"
                             class="mb-6 flex flex-col lg:flex-row justify-between px-8 p-4 border rounded-md shadow-sm">
                            <!-- Base Amount -->
                            <div class="flex flex-col gap-4 p-4 text-center lg:text-left">
                                <p class="font-bold">Base Amount(<span class="text-sm">₹ {{ state.selectedUnit.formatted_base_amount }}</span>)</p>
                                <p class="text-xl"><span class="text-green-600">Received</span> /<span
                                    class="text-red-500"> Due </span></p>
                                <p class="text-2xl">
                                    <span
                                        class="text-green-600">₹ {{ state.selectedUnit.formatted_base_received_amount }}</span>
                                    /
                                    <span class="text-red-500">₹ {{ state.selectedUnit.formatted_base_due_amount }}</span>
                                </p>
                            </div>
                            <div class="border-r border-teal-800"></div>

                            <!-- GST Amount -->
                            <div class="flex flex-col gap-4 p-4 text-center lg:text-left">
                                <p class="font-bold">GST Amount(<span class="text-sm">₹ {{ state.selectedUnit.formatted_gst_amount }}</span>)</p>
                                <p class="text-xl"><span class="text-green-600">Received</span> /<span
                                    class="text-red-500"> Due </span></p>
                                <p class="text-2xl">
                                    <span
                                        class="text-green-600">₹ {{ state.selectedUnit.formatted_gst_received_amount }}</span>
                                    /
                                    <span class="text-red-500">₹ {{ state.selectedUnit.formatted_gst_due_amount }}</span>
                                </p>
                            </div>
                            <div class="border-r border-teal-800"></div>
                            <!-- Total -->
                            <div class="flex flex-col gap-4 p-4 text-center lg:text-left">
                                <p class="font-bold">Total Amount(<span class="text-sm">₹ {{ state.selectedUnit.formatted_total_amount }}</span>)</p>
                                <!-- <p class="text-2xl">
                                    ₹ {{ state.selectedUnit.formatted_total_amount || '—' }}
                                </p> -->
                                <p class="text-xl"><span class="text-green-600">Received</span> /<span
                                    class="text-red-500"> Due </span></p>
                                <p class="text-2xl">
                                    <span class="text-green-600">₹ {{ state.selectedUnit.formatted_total_received_amount
                                        }}</span> /
                                    <span class="text-red-500">₹ {{ state.selectedUnit.formatted_total_due_amount
                                        }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="lg:px-6 py-10 bg-gray-100 lg:border lg:border-primary rounded-md lg:shadow">
                            <h3 class="text-2xl font-bold text-primary text-center mb-6">
                                Transaction Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:gap-10 gap-5">
                                <!-- Payment Date -->
                                <div>
                                    <FormInput label="Date of Payment" hint="(This will be displayed on your
                                            receipts)" v-model="form.payment_date" type="date"
                                               :error="form.errors.payment_date" :required="true" />
                                </div>

                                <!-- Receipt Date -->
                                <div>
                                    <FormInput label="Date of Receipt" hint="(This will be displayed on your
                                            receipts)" v-model="form.receipt_date" type="date"
                                               :error="form.errors.receipt_date" :required="true" />
                                </div>

                                <!-- Transaction Amount -->
                                <div>
                                    <FormInput label="Transaction Amount" hint="(This will be displayed on your
                                            receipts)" v-model="form.transaction_amount" type="text"
                                               :error="form.errors.transaction_amount" :required="true" />
                                </div>

                                <!-- GST -->
                                <div>
                                    <label class="block font-medium text-gray-700 mb-1">
                                        GST
                                        <span class="text-gray-400 text-[12px]">(This will be displayed on your
                                            receipts)</span>
                                    </label>
                                    <select v-model="form.gst"
                                            class="w-full border border-gray-300 rounded-md px-4 py-3 bg-white focus:ring focus:ring-cyan-100 focus:outline-none">
                                        <option disabled :value="null">Select</option>
                                        <option :value="false">Base</option>
                                        <option :value="true">GST</option>
                                    </select>
                                </div>


                                <!-- Payment Type -->
                                <div>
                                    <label class="block font-medium text-gray-700 mb-1">
                                        Payment Type
                                        <span class="text-gray-400 text-[12px]">(This will be displayed on your
                                            receipts)</span>
                                    </label>
                                    <select v-model="form.payment_type"
                                            class="w-full border border-gray-300 rounded-md px-4 py-3 bg-white focus:ring focus:ring-cyan-100 focus:outline-none">
                                        <option disabled value="">Select Payment Type</option>
                                        <option value="bank_draft">Bank Draft</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="rtgs">RTGS</option>
                                        <option value="neft">NEFT</option>
                                        <option value="cash">Cash</option>
                                        <option value="imps">IMPS</option>
                                    </select>
                                </div>

                                <!-- Payment Reference -->
                                <div>
                                    <FormInput label="Payment Reference No" hint="(Not required for cash)"
                                               v-model="form.payment_reference" type="text"
                                               :error="form.errors.payment_reference"
                                               :required="form.payment_type !== 'cash'" />
                                </div>

                                <!-- Bank Name -->
                                <div>
                                    <FormInput label="Bank Name" hint="(This will be displayed on your
                                            receipts)" v-model="form.bank_name" type="text"
                                               :error="form.errors.bank_name" :required="true" />
                                </div>

                                <!-- Bank Branch -->
                                <div>
                                    <FormInput label="Bank Branch" hint="(This will be displayed on your
                                            receipts)" v-model="form.bank_branch" type="text"
                                               :error="form.errors.bank_branch" :required="true" />
                                </div>
                            </div>


                            <div class="mt-6 text-right">
                                <button @click="submitTransaction"
                                        class="bg-primary w-full text-white px-6 py-2 rounded-lg hover:bg-teal-700">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
