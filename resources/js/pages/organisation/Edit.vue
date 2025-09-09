<script setup>
import { computed, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import FormInput from '@/components/FormInput.vue'
import { Icon } from "@iconify/vue";

const props = defineProps(['organisation', "disabled"])
const imagePreview = ref(props.organisation.logo || '')
const toast = new ToastMagic();
const form = useForm({
    name: props.organisation.name || '',
    mobile: props.organisation.mobile || '',
    email: props.organisation.email || '',
    logo: null,
    seperate_sequence_for_gst: props.organisation.seperate_sequence_for_gst || false, // GST column,
    isChangeToggleGst: false, // to track if toggle is changed
    disable_seperate_sequence_for_gst: props.organisation.disable_seperate_sequence_for_gst || false, // to disable toggle if needed
})



function handleFileSelect(event) {
    const file = event.target.files[0]
    form.logo = file

    if (file) {
        const reader = new FileReader()
        reader.onload = e => {
            imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
    } else {
        imagePreview.value = ''
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('organisation.update', { organisation: props.organisation.id }), {
        preserveState: true,
        onSuccess: () => {
            router.visit(route('organisation.edit', { organisation: props.organisation.id }), {
                preserveState: true,
            });
            toast.success("Organization updated successfully!");
        },
        onError: (errors) => {
            const firstError = Object.keys(errors)[0];
            toast.error(firstError || "An error occurred while updating the organisation.");
            console.error('Update failed:', errors);
        }
    });
}

// toggle button functionality

function toggleActive() {
    form.isChangeToggleGst = !form.isChangeToggleGst; // update the form value
    form.seperate_sequence_for_gst = !form.seperate_sequence_for_gst; // toggle the form value
}

</script>

<template>
    <AppLayout>
        <div class="py-0 lg:py-10 mt-10 lg:mt-0">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <div class="bg-gray-100 shadow-md rounded-lg overflow-hidden border-t-4 border-primary">
                    <div class="text-gray-900 px-4 pt-8">
                        <h6 class="m-0 font-bold text-primary text-center lg:text-3xl">
                            Organisation
                        </h6>

                        <!-- Form -->
                        <div class="lg:p-7 pb-10 ">
                            <div class="grid grid-cols-1 gap-y-4">

                                <!-- Name -->
                                <div>
                                    <FormInput label="Organisation Name" v-model="form.name" :error="form.errors.name"
                                        required />
                                </div>

                                <!-- Mobile -->
                                <div>
                                    <FormInput label="Organisation Mobile No." v-model="form.mobile"
                                        :error="form.errors.mobile" required />
                                </div>

                                <!-- Email -->
                                <div>
                                    <FormInput label="Organisation Email ID" v-model="form.email"
                                        :error="form.errors.email" required />
                                </div>

                                <!-- Logo -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Organisation Logo</label>
                                    <input type="text"
                                        :value="form.logo ? form.logo.name : (props.organisation.logo || '')"
                                        class="mt-1 block w-full px-3 py-3 rounded-md bg-white shadow-sm focus:ring-cyan-700 focus:border-cyan-700 cursor-pointer border border-gray-300"
                                        placeholder="Click to upload" readonly @click="$refs.logoInput.click()"
                                        required />
                                    <input type="file" ref="logoInput"
                                        accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden"
                                        @change="handleFileSelect" />
                                    <p v-if="form.errors.logo" class="mt-2 text-sm text-red-600">{{ form.errors.logo }}
                                    </p>

                                    <!-- Preview -->
                                    <div
                                        class="mt-4 w-full h-[150px] bg-white flex items-center justify-center rounded-md shadow-sm border border-gray-300">
                                        <img v-if="imagePreview" :src="imagePreview" class="h-full object-cover" />
                                        <span v-else class="text-gray-500">Image preview will appear here...</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex mt-6 justify-between items-center">
                                <div class="flex items-center space-x-2">
                                    <span class="mr-1 text-gray-700">
                                        Separate Sequence For GST Receipt?
                                    </span>
                                    <Icon icon="material-symbols-light:info-outline" width="24" height="24" />
                                </div>
                                <div>
                                    <button :disabled="disabled" @click="toggleActive" :class="[
                                        'relative inline-flex items-center h-6 rounded-full w-12 transition-colors focus:outline-none',
                                        form.seperate_sequence_for_gst ? 'bg-primary' : 'bg-gray-300',
                                        disabled ? 'opacity-50 cursor-not-allowed' : '' // styling for disabled
                                    ]">
                                        <span :class="[
                                            'inline-block w-6 h-6 transform bg-white rounded-full shadow-md transition-transform',
                                            form.seperate_sequence_for_gst ? 'translate-x-6' : 'translate-x-0'
                                        ]"></span>
                                    </button>
                                    <span class="ml-3 text-gray-700">{{ form.seperate_sequence_for_gst ? 'Yes' : 'No'
                                        }}</span>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex  justify-center mt-6 gap-7">
                                <button type="submit" @click="submit"
                                    class="text-white bg-primary hover:bg-teal-800 font-medium rounded-lg text-sm md:text-base px-10 py-2.5 md:px-[10rem]">
                                    Submit
                                </button>
                                <button type="reset" @click="form.reset()"
                                    class="text-white bg-teal-600 hover:bg-teal-500 font-medium rounded-lg text-sm md:text-base px-10 py-2.5 md:px-[10rem]">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
