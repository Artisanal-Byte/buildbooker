<script setup>
import { ref } from 'vue';
import { useForm, usePage, Link, router } from '@inertiajs/vue3';
import FormInput from '@/components/FormInput.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FormTextarea from '@/components/FormTextarea.vue';
const toast = new ToastMagic();
import BackButton from '@/components/BackButton.vue';
const props = defineProps(['errors']);

const form = useForm({
    name: '',
    jurisdiction: '',
    office_display_address: '',
    site_display_address: '',
    total_units: '',
    rera_no: '',
    logo_file: null,
});

const logoName = ref('');
const logoPreview = ref(null);

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo_file = file;
        logoName.value = file.name;

        const reader = new FileReader();
        reader.onload = (event) => {
            logoPreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        form.logo_file = null;
        logoName.value = '';
        logoPreview.value = null;
    }
};

const submit = () => {
    form.post(route('projects.store'), {
        onSuccess: () => {
            router.visit(route('projects.index'))
            toast.success("Project created successfully!");
        },
        onError: () => {
            toast.error("Failed to create customer. please fill the all the required fields.");
        }

    });
};

const resetForm = () => {
    form.reset();
    logoName.value = '';
    logoPreview.value = null;
};
</script>

<template>
    <AppLayout>
        <div class="py-0 lg:py-10">
            <div class="max-w-4xl mx-auto px-6 py-5 lg:px-8">
                <div class="flex items-start justify-start mb-4">
                    <BackButton :prevRoute="route('projects.index')" />
                </div>
               
                <div class="bg-gray-100 overflow-hidden shadow-md rounded-lg border-t-4 border-primary">
                    <div class="text-gray-900">
                        <div class="flex items-center justify-between pt-8 py-4 border-gray-200 px-4">
                            <h6
                                class="m-0 font-bold text-primary text-center lg:text-3xl w-full">
                                Create New Project
                            </h6>
                        </div>

                        <form @submit.prevent="submit" id="new_project_form" class="p-4 lg:p-7 bg-gray-100 rounded">
                            <div class="grid grid-cols-1 gap-y-4">
                                <!-- Project Name -->
                                <FormInput label="Project Name" hint="(This will be displayed on your receipts)"
                                    id="name" :required="true" v-model="form.name" :error="errors.name" />

                                <!-- Project Logo -->
                                <div>
                                    <label for="logo_file" class="block text-sm font-medium text-gray-700">
                                        <span class="text-red-500 text-xs align-super">*</span>
                                        Project Logo
                                        <span class="ml-1 text-gray-400 text-xs">(JPG, JPEG, PNG, WEBP | Less than 2
                                            MB)</span>
                                    </label>
                                    <input type="text" id="logo_selector" :value="logoName"
                                        placeholder="Click to upload"
                                        class="mt-1 block w-full px-3 py-3 bg-white border border-gary-400 rounded-md shadow-sm focus:ring-cyan-700 focus:border-cyan-700 cursor-pointer"
                                        readonly @click="$refs.imageUpload.click()" />
                                    <input ref="imageUpload" type="file" name="logo_file"
                                        accept="image/jpeg, image/jpg, image/png, image/webp" class="hidden"
                                        @change="handleFileSelect" />
                                    <p v-if="errors.logo_file" class="mt-2 text-sm text-red-600">{{ errors.logo_file }}
                                    </p>

                                    <div class="mt-4 w-full h-[150px] bg-white border border-gary-400 flex items-center justify-center rounded-md shadow-sm border border-gray-300"
                                        id="image_preview">
                                        <img v-if="logoPreview" :src="logoPreview" alt="Preview"
                                            class="h-full object-cover" />
                                        <span v-else class="text-gray-500">Image preview will appear here...</span>
                                    </div>
                                </div>

                                <!-- Jurisdiction -->
                                <FormInput label="Governing Jurisdiction"
                                    hint="(This will be displayed on your receipts)" :required="true" id="jurisdiction"
                                    v-model="form.jurisdiction" :error="errors.jurisdiction" />

                                <!-- Office Address -->
                                <FormTextarea label="Office Address" hint="(This will be displayed on your receipts)"
                                    :required="true" id="office_display_address" v-model="form.office_display_address"
                                    :error="errors.office_display_address" />

                                <!-- Site Address -->
                                <FormTextarea label="Site Address" hint="(This will be displayed on your receipts)"
                                    :required="true" id="site_display_address" v-model="form.site_display_address"
                                    :error="errors.site_display_address" />

                                <!-- Total Units -->
                                <FormInput label="Total Number Of Units" type="number" :required="true" id="total_units"
                                    v-model="form.total_units" :error="errors.total_units" />

                                <!-- RERA No. -->
                                <FormInput label="Project RERA No." hint="(Optional)" id="rera_no"
                                    v-model="form.rera_no" :error="errors.rera_no" :required="true" />
                            </div>

                            <!-- Submit & Reset Button -->
                            <div class="flex flex-wrap justify-between mt-6 gap-4">
                                <button type="submit"
                                    class="text-white bg-primary hover:bg-teal-800 focus:ring-4 focus:ring-teal-700 font-medium rounded-lg text-sm md:text-base px-10 py-2.5 md:px-[10rem]">
                                    Submit
                                </button>
                                <button type="reset" @click="resetForm"
                                    class="text-white bg-teal-500 hover:bg-teal-500 focus:ring-4 focus:ring-teal-400 font-medium rounded-lg text-sm md:text-base px-10 py-2.5 md:px-[10rem]">
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
