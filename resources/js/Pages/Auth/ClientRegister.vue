<script setup>
import { onMounted, ref, watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const form = useForm({
    company_name: '',
    business_type: '',
    tin_number: '',
    contact_person: '',
    email: '',
    phone_country: '+63',
    phone_raw: '',
    phone: '',
    password: '',
    password_confirmation: '',
    company_address: '',
});

const isLoaded = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);

// Field-specific error messages
const fieldErrors = ref({
    company_name: '',
    business_type: '',
    tin_number: '',
    contact_person: '',
    email: '',
    phone_raw: '',
    password: '',
    password_confirmation: '',
    company_address: ''
});

const inputWarnings = ref({
    company_name: '',
    contact_person: '',
    company_address: '',
    email: '',
    tin_number: '',
    phone_raw: ''
});

let warningTimeouts = {};

onMounted(() => {
    isLoaded.value = true;
});

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const toggleConfirmPassword = () => {
    showConfirmPassword.value = !showConfirmPassword.value;
};

const triggerWarning = (field, message) => {
    inputWarnings.value[field] = message;
    if (warningTimeouts[field]) clearTimeout(warningTimeouts[field]);
    warningTimeouts[field] = setTimeout(() => {
        inputWarnings.value[field] = '';
    }, 3000);
};

// Validation functions for each field
const validateCompanyName = () => {
    const value = form.company_name;
    if (!value || value.trim() === '') {
        fieldErrors.value.company_name = 'Company name is required';
        return false;
    }
    if (value.length < 2) {
        fieldErrors.value.company_name = 'Company name must be at least 2 characters';
        return false;
    }
    if (!/^[a-zA-Z\s&.,-]+$/.test(value)) {
        fieldErrors.value.company_name = 'Company name contains invalid characters';
        return false;
    }
    fieldErrors.value.company_name = '';
    return true;
};

const validateBusinessType = () => {
    const value = form.business_type;
    if (!value || value.trim() === '') {
        fieldErrors.value.business_type = 'Business type is required';
        return false;
    }
    if (value.length < 2) {
        fieldErrors.value.business_type = 'Business type must be at least 2 characters';
        return false;
    }
    fieldErrors.value.business_type = '';
    return true;
};

const validateTinNumber = () => {
    const value = form.tin_number;
    if (value && value.trim() !== '') {
        // Remove all dashes to count only digits
        const digitsOnly = value.replace(/-/g, '');
        const digitCount = digitsOnly.length;
        
        // Check if digit count is either 9 or 12
        if (digitCount !== 9 && digitCount !== 12) {
            fieldErrors.value.tin_number = 'TIN number must contain either 9 digits (format: XXX-XXX-XXX) or 12 digits (format: XXX-XXX-XXX-XXX)';
            return false;
        }
        
        // Check if all characters are digits
        if (!/^\d+$/.test(digitsOnly)) {
            fieldErrors.value.tin_number = 'TIN number must contain only digits (no letters allowed)';
            return false;
        }
        
        // Check format based on digit count
        if (digitCount === 9) {
            // Format for 9 digits: XXX-XXX-XXX
            const formatPattern9 = /^\d{3}-\d{3}-\d{3}$/;
            if (!formatPattern9.test(value)) {
                fieldErrors.value.tin_number = 'TIN number must follow the format: XXX-XXX-XXX';
                return false;
            }
        } else if (digitCount === 12) {
            // Format for 12 digits: XXX-XXX-XXX-XXX
            const formatPattern12 = /^\d{3}-\d{3}-\d{3}-\d{3}$/;
            if (!formatPattern12.test(value)) {
                fieldErrors.value.tin_number = 'TIN number must follow the format: XXX-XXX-XXX-XXX';
                return false;
            }
        }
    }
    fieldErrors.value.tin_number = '';
    return true;
};

const validateContactPerson = () => {
    const value = form.contact_person;
    if (!value || value.trim() === '') {
        fieldErrors.value.contact_person = 'Contact person name is required';
        return false;
    }
    if (value.length < 2) {
        fieldErrors.value.contact_person = 'Contact person name must be at least 2 characters';
        return false;
    }
    if (!/^[a-zA-Z\s.]+$/.test(value)) {
        fieldErrors.value.contact_person = 'Contact person name must contain only letters and spaces';
        return false;
    }
    fieldErrors.value.contact_person = '';
    return true;
};

const validateEmail = () => {
    const value = form.email;
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!value || value.trim() === '') {
        fieldErrors.value.email = 'Business email is required';
        return false;
    }
    if (!emailRegex.test(value)) {
        fieldErrors.value.email = 'Please enter a valid email address';
        return false;
    }
    fieldErrors.value.email = '';
    return true;
};

const validatePhoneRaw = () => {
    const value = form.phone_raw;
    if (!value || value.trim() === '') {
        fieldErrors.value.phone_raw = 'Phone number is required';
        return false;
    }
    if (value.length !== 11) {
        fieldErrors.value.phone_raw = 'Phone number must be exactly 11 digits';
        return false;
    }
    if (!/^\d{11}$/.test(value)) {
        fieldErrors.value.phone_raw = 'Phone number must contain only digits (no letters allowed)';
        return false;
    }
    fieldErrors.value.phone_raw = '';
    return true;
};

const validateCompanyAddress = () => {
    const value = form.company_address;
    if (!value || value.trim() === '') {
        fieldErrors.value.company_address = 'Company address is required';
        return false;
    }
    if (value.length < 5) {
        fieldErrors.value.company_address = 'Address must be at least 5 characters';
        return false;
    }
    fieldErrors.value.company_address = '';
    return true;
};

const validatePassword = () => {
    const value = form.password;
    if (!value) {
        fieldErrors.value.password = 'Password is required';
        return false;
    }
    if (value.length < 8) {
        fieldErrors.value.password = 'Password must be at least 8 characters';
        return false;
    }
    if (!/(?=.*[a-z])/.test(value)) {
        fieldErrors.value.password = 'Password must contain at least one lowercase letter';
        return false;
    }
    if (!/(?=.*[A-Z])/.test(value)) {
        fieldErrors.value.password = 'Password must contain at least one uppercase letter';
        return false;
    }
    if (!/(?=.*\d)/.test(value)) {
        fieldErrors.value.password = 'Password must contain at least one number';
        return false;
    }
    fieldErrors.value.password = '';
    return true;
};

const validatePasswordConfirmation = () => {
    const password = form.password;
    const confirmation = form.password_confirmation;
    if (!confirmation) {
        fieldErrors.value.password_confirmation = 'Please confirm your password';
        return false;
    }
    if (password !== confirmation) {
        fieldErrors.value.password_confirmation = 'Passwords do not match';
        return false;
    }
    fieldErrors.value.password_confirmation = '';
    return true;
};

// Validate all fields before submission
const validateForm = () => {
    const isCompanyNameValid = validateCompanyName();
    const isBusinessTypeValid = validateBusinessType();
    const isTinNumberValid = validateTinNumber();
    const isContactPersonValid = validateContactPerson();
    const isEmailValid = validateEmail();
    const isPhoneValid = validatePhoneRaw();
    const isAddressValid = validateCompanyAddress();
    const isPasswordValid = validatePassword();
    const isPasswordConfirmValid = validatePasswordConfirmation();
    
    return isCompanyNameValid && isBusinessTypeValid && isTinNumberValid && 
           isContactPersonValid && isEmailValid && isPhoneValid && 
           isAddressValid && isPasswordValid && isPasswordConfirmValid;
};

// Keypress blocks
const blockNumbersAndSpecial = (e, field) => {
    if (e.key.length === 1 && !/^[a-zA-Z\s]$/.test(e.key)) {
        e.preventDefault();
        triggerWarning(field, 'Numbers and special characters are not allowed.');
    }
};

// Block ALL letters (including 'e') and only allow numbers
const blockNonNumeric = (e, field) => {
    // Allow control keys (backspace, delete, tab, etc.)
    if (e.key === 'Backspace' || e.key === 'Delete' || e.key === 'Tab' || e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Home' || e.key === 'End') {
        return;
    }
    // Block any letter (a-z, A-Z) including 'e'
    if (/[a-zA-Z]/i.test(e.key)) {
        e.preventDefault();
        triggerWarning(field, 'Letters are not allowed. Numbers only.');
    }
    // Block any other non-numeric characters
    else if (e.key.length === 1 && !/^\d$/.test(e.key)) {
        e.preventDefault();
        triggerWarning(field, 'Only numbers (0-9) are allowed.');
    }
};

const blockSpecialForAddress = (e) => {
    if (e.key.length === 1 && !/^[a-zA-Z0-9\s.,#-]$/.test(e.key)) {
        e.preventDefault();
        triggerWarning('company_address', 'Only letters, numbers, spaces, dots, commas, and hyphens are allowed.');
    }
};

const blockSpecialForEmail = (e) => {
    if (e.key.length === 1 && !/^[a-zA-Z0-9@.\-]$/.test(e.key)) {
        e.preventDefault();
        triggerWarning('email', 'Invalid character. Only letters, numbers, @, ., and - are allowed.');
    }
};

// Enforce exactly 11 digits for phone
const enforcePhoneLimit = (e) => {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) {
        value = value.substring(0, 11);
        form.phone_raw = value;
    }
    validatePhoneRaw();
};

// Format TIN number - supports both 9-digit and 12-digit formats
const formatTinNumber = (e) => {
    // Get the input value and remove all non-digits
    let value = e.target.value.replace(/-/g, '').replace(/\D/g, '');
    
    // Allow up to 12 digits maximum (can be 9 or 12)
    if (value.length > 12) {
        value = value.substring(0, 12);
    }
    
    let formatted = '';
    
    // Format based on number of digits
    if (value.length <= 3) {
        // Less than or equal to 3 digits: just show the digits
        formatted = value;
    } else if (value.length <= 6) {
        // 4-6 digits: format as XXX-XXX
        formatted = value.substring(0, 3) + '-' + value.substring(3);
    } else if (value.length <= 9) {
        // 7-9 digits: format as XXX-XXX-XXX
        formatted = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6);
    } else {
        // 10-12 digits: format as XXX-XXX-XXX-XXX
        formatted = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 9) + '-' + value.substring(9, 12);
    }
    
    // Update the form value
    if (form.tin_number !== formatted) {
        form.tin_number = formatted;
    }
    
    // Validate the TIN number
    validateTinNumber();
};

// Prevent pasting of invalid TIN numbers
const handleTinPaste = (e) => {
    e.preventDefault();
    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
    // Extract only digits from pasted text (remove any letters including 'e')
    const digitsOnly = pastedText.replace(/\D/g, '');
    // Take only first 12 digits
    const limitedDigits = digitsOnly.substring(0, 12);
    
    let formatted = '';
    
    // Format based on number of digits
    if (limitedDigits.length <= 3) {
        formatted = limitedDigits;
    } else if (limitedDigits.length <= 6) {
        formatted = limitedDigits.substring(0, 3) + '-' + limitedDigits.substring(3);
    } else if (limitedDigits.length <= 9) {
        formatted = limitedDigits.substring(0, 3) + '-' + limitedDigits.substring(3, 6) + '-' + limitedDigits.substring(6);
    } else {
        formatted = limitedDigits.substring(0, 3) + '-' + limitedDigits.substring(3, 6) + '-' + limitedDigits.substring(6, 9) + '-' + limitedDigits.substring(9, 12);
    }
    
    form.tin_number = formatted;
    validateTinNumber();
};

// Prevent pasting of non-numeric content for phone
const handlePhonePaste = (e) => {
    e.preventDefault();
    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
    // Extract only digits from pasted text (remove any letters including 'e')
    const digitsOnly = pastedText.replace(/\D/g, '');
    // Take only first 11 digits
    const limitedDigits = digitsOnly.substring(0, 11);
    form.phone_raw = limitedDigits;
    validatePhoneRaw();
};

// Paste sanitization watchers
watch(() => form.company_name, (val) => {
    const filtered = val.replace(/[^a-zA-Z\s]/g, '');
    if (val !== filtered) form.company_name = filtered;
    validateCompanyName();
});

watch(() => form.business_type, (val) => {
    validateBusinessType();
});

watch(() => form.contact_person, (val) => {
    const filtered = val.replace(/[^a-zA-Z\s]/g, '');
    if (val !== filtered) form.contact_person = filtered;
    validateContactPerson();
});

watch(() => form.company_address, (val) => {
    const filtered = val.replace(/[^a-zA-Z0-9\s.,#-]/g, '');
    if (val !== filtered) form.company_address = filtered;
    validateCompanyAddress();
});

watch(() => form.email, (val) => {
    const filtered = val.replace(/[^a-zA-Z0-9@.\-]/g, '');
    if (val !== filtered) form.email = filtered;
    validateEmail();
});

watch(() => form.tin_number, (val) => {
    if (!val) {
        fieldErrors.value.tin_number = '';
        return;
    }
    validateTinNumber();
});

watch(() => form.phone_raw, (val) => {
    const filtered = val.replace(/\D/g, '').substring(0, 11);
    if (val !== filtered) form.phone_raw = filtered;
    validatePhoneRaw();
});

watch(() => form.password, () => {
    validatePassword();
    if (form.password_confirmation) {
        validatePasswordConfirmation();
    }
});

watch(() => form.password_confirmation, () => {
    validatePasswordConfirmation();
});

const submit = () => {
    if (!validateForm()) {
        toast.error('Please fix the errors in the form before submitting.');
        return;
    }
    
    form.phone = form.phone_country + form.phone_raw;

    form.post(route('client.register.store'), {
        onSuccess: () => {
            toast.success('Registration submitted successfully! Please wait for approval.');
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors)[0] || 'Registration failed. Please check the form.';
            toast.error(errorMsg);
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="relative min-h-screen flex flex-col bg-cover bg-center bg-no-repeat"
        style="background-image: url('/images/threads.jpg');">
        <!-- Overlay for better text readability -->
        <div class="absolute inset-0 bg-black/35"></div>

        <!-- Top navigation with logo -->
        <nav class="relative z-30 px-6 py-5 flex items-center">
            <Link href="/" class="flex items-center gap-3 group">
                <div
                    class="size-10 sm:size-11 p-2.5 bg-white/90 backdrop-blur-sm rounded-xl shadow-md group-hover:scale-105 transition-transform duration-300">
                    <img src="/images/applogo.png" alt="Monti Textile Logo" class="h-full w-full object-contain" />
                </div>
                <span class="font-black text-2xl tracking-tight text-white drop-shadow-md">
                    Monti<span class="text-blue-300">Textile</span>
                </span>
            </Link>
        </nav>

        <!-- Main content area -->
        <div class="relative z-10 flex-grow flex items-center justify-center px-5 pb-12 pt-4">
            <div class="w-full max-w-3xl">

                <div
                    class="backdrop-blur-lg bg-white/15 border border-white/20 rounded-3xl shadow-2xl shadow-black/30 overflow-hidden">

                    <div class="px-8 pt-10 pb-10 sm:px-12 sm:pt-12 sm:pb-12">

                        <Head title="Monti Textile - Partner Registration" />

                        <div class="text-center mb-10">
                            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight drop-shadow-lg">
                                Apply for Partnership
                            </h1>
                            <p class="mt-3 text-slate-200 text-base sm:text-lg max-w-2xl mx-auto">
                                Please fill in your company details to request access to the Monti Textile B2B Partner
                                Portal.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div>
                                    <InputLabel for="company_name" value="Company Name" class="text-white/90" />
                                    <TextInput id="company_name" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        :class="{ 'border-red-400 ring-red-400/40': fieldErrors.company_name }"
                                        v-model="form.company_name" required
                                        @keypress="blockNumbersAndSpecial($event, 'company_name')"
                                        @blur="validateCompanyName"
                                        placeholder="Your Company Name" />
                                    <InputError class="mt-1 text-red-300" :message="form.errors.company_name" />
                                    <p v-if="inputWarnings.company_name" class="mt-1 text-xs text-red-300">{{
                                        inputWarnings.company_name }}</p>
                                    <p v-if="fieldErrors.company_name" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.company_name }}</p>
                                </div>

                                <div>
                                    <InputLabel for="business_type" value="Business Type" class="text-white/90" />
                                    <select id="business_type"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border-white/30 text-white rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        :class="{ 'border-red-400 ring-red-400/40': fieldErrors.business_type }"
                                        v-model="form.business_type" required @change="validateBusinessType">
                                        <option value="" class="text-gray-900">Select Business Type</option>
                                        <option value="Garment Manufacturer" class="text-gray-900">Garment Manufacturer</option>
                                        <option value="Retailer" class="text-gray-900">Retailer</option>
                                        <option value="Exporter" class="text-gray-900">Exporter</option>
                                        <option value="Wholesaler" class="text-gray-900">Wholesaler</option>
                                        <option value="Distributor" class="text-gray-900">Distributor</option>
                                        <option value="Other" class="text-gray-900">Other</option>
                                    </select>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.business_type" />
                                    <p v-if="fieldErrors.business_type" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.business_type }}</p>
                                </div>

                                <div>
                                    <InputLabel for="tin_number" value="TIN Number (Optional)" class="text-white/90" />
                                    <TextInput id="tin_number" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        :class="{ 'border-red-400 ring-red-400/40': fieldErrors.tin_number }"
                                        v-model="form.tin_number" 
                                        @input="formatTinNumber"
                                        @paste="handleTinPaste"
                                        @keypress="blockNonNumeric($event, 'tin_number')"
                                        @blur="validateTinNumber"
                                        placeholder="123-456-789 or 123-456-789-012" 
                                        maxlength="15" />
                                    <InputError class="mt-1 text-red-300" :message="form.errors.tin_number" />
                                    <p v-if="fieldErrors.tin_number" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.tin_number }}</p>
                                    <p class="mt-1 text-xs text-white/50">Format: XXX-XXX-XXX (9 digits) or XXX-XXX-XXX-XXX (12 digits) - Numbers only, no letters</p>
                                </div>

                                <div>
                                    <InputLabel for="contact_person" value="Contact Person" class="text-white/90" />
                                    <TextInput id="contact_person" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        :class="{ 'border-red-400 ring-red-400/40': fieldErrors.contact_person }"
                                        v-model="form.contact_person" required
                                        @keypress="blockNumbersAndSpecial($event, 'contact_person')"
                                        @blur="validateContactPerson"
                                        placeholder="Full Name" />
                                    <InputError class="mt-1 text-red-300" :message="form.errors.contact_person" />
                                    <p v-if="inputWarnings.contact_person" class="mt-1 text-xs text-red-300">{{
                                        inputWarnings.contact_person }}</p>
                                    <p v-if="fieldErrors.contact_person" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.contact_person }}</p>
                                </div>

                                <div>
                                    <InputLabel for="email" value="Business Email" class="text-white/90" />
                                    <TextInput id="email" type="email"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        :class="{ 'border-red-400 ring-red-400/40': fieldErrors.email }"
                                        v-model="form.email" required autocomplete="username"
                                        @keypress="blockSpecialForEmail"
                                        @blur="validateEmail"
                                        placeholder="company@example.com" />
                                    <InputError class="mt-1 text-red-300" :message="form.errors.email" />
                                    <p v-if="fieldErrors.email" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.email }}</p>
                                </div>

                                <div>
                                    <InputLabel for="phone_raw" value="Phone Number (11 digits)" class="text-white/90" />
                                    <div class="flex">
                                        <span
                                            class="inline-flex items-center px-4 py-3 bg-white/10 border border-r-0 border-white/30 rounded-l-xl text-white">
                                            {{ form.phone_country }}
                                        </span>
                                        <TextInput id="phone_raw" type="tel"
                                            class="flex-1 block py-3 px-4 bg-white/15 border-white/30 text-white placeholder:text-slate-300 rounded-r-xl focus:border-blue-400 focus:ring-blue-400/40"
                                            :class="{ 'border-red-400 ring-red-400/40': fieldErrors.phone_raw }"
                                            v-model="form.phone_raw" 
                                            @keypress="blockNonNumeric($event, 'phone_raw')"
                                            @input="enforcePhoneLimit"
                                            @paste="handlePhonePaste"
                                            @blur="validatePhoneRaw"
                                            placeholder="91234567890" 
                                            maxlength="11" />
                                    </div>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.phone" />
                                    <p v-if="inputWarnings.phone_raw" class="mt-1 text-xs text-red-300">{{
                                        inputWarnings.phone_raw }}</p>
                                    <p v-if="fieldErrors.phone_raw" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.phone_raw }}</p>
                                    <p class="mt-1 text-xs text-white/50">Enter exactly 11 digits (numbers only, no letters)</p>
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="company_address" value="Company Address" class="text-white/90" />
                                    <TextInput id="company_address" type="text"
                                        class="mt-1 block w-full py-3 px-4 bg-white/15 border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                        :class="{ 'border-red-400 ring-red-400/40': fieldErrors.company_address }"
                                        v-model="form.company_address" required 
                                        @keypress="blockSpecialForAddress"
                                        @blur="validateCompanyAddress"
                                        placeholder="Full company address" />
                                    <InputError class="mt-1 text-red-300" :message="form.errors.company_address" />
                                    <p v-if="fieldErrors.company_address" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.company_address }}</p>
                                </div>

                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">

                                <div>
                                    <InputLabel for="password" value="Password" class="text-white/90" />
                                    <div class="relative">
                                        <TextInput id="password" :type="showPassword ? 'text' : 'password'"
                                            class="mt-1 pr-12 block w-full py-3 px-4 bg-white/15 border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                            :class="{ 'border-red-400 ring-red-400/40': fieldErrors.password }"
                                            v-model="form.password" required autocomplete="new-password"
                                            @blur="validatePassword"
                                            placeholder="••••••••" />
                                        <button type="button" @click="togglePassword"
                                            class="absolute inset-y-0 right-4 flex items-center text-slate-300 hover:text-white">
                                            <svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="fieldErrors.password" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.password }}</p>
                                    <InputError class="mt-1 text-red-300" :message="form.errors.password" />
                                    <p class="mt-1 text-xs text-white/50">Minimum 8 characters with uppercase, lowercase, and number</p>
                                </div>

                                <div>
                                    <InputLabel for="password_confirmation" value="Confirm Password"
                                        class="text-white/90" />
                                    <div class="relative">
                                        <TextInput id="password_confirmation"
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            class="mt-1 pr-12 block w-full py-3 px-4 bg-white/15 border-white/30 text-white placeholder:text-slate-300 rounded-xl focus:border-blue-400 focus:ring-blue-400/40"
                                            :class="{ 'border-red-400 ring-red-400/40': fieldErrors.password_confirmation }"
                                            v-model="form.password_confirmation" required 
                                            @blur="validatePasswordConfirmation"
                                            placeholder="••••••••"
                                            autocomplete="new-password" />
                                        <button type="button" @click="toggleConfirmPassword"
                                            class="absolute inset-y-0 right-4 flex items-center text-slate-300 hover:text-white">
                                            <svg v-if="!showConfirmPassword" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="fieldErrors.password_confirmation" class="mt-1 text-xs text-red-300 font-medium">{{
                                        fieldErrors.password_confirmation }}</p>
                                    <InputError class="mt-1 text-red-300"
                                        :message="form.errors.password_confirmation" />
                                </div>

                            </div>

                            <div
                                class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-8 border-t border-white/10 mt-6">
                                <Link :href="route('client.login')"
                                    class="group text-sm font-medium text-slate-300 hover:text-white transition-colors flex items-center">
                                    <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    Back to Sign in
                                </Link>

                                <PrimaryButton
                                    class="w-full sm:w-auto px-10 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl shadow-lg shadow-blue-700/30 transition-all duration-200"
                                    :class="{ 'opacity-60 cursor-wait': form.processing }" :disabled="form.processing">
                                    <span v-if="form.processing">Submitting...</span>
                                    <span v-else>Apply for Partnership</span>
                                </PrimaryButton>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input,
select,
textarea {
    @apply transition-all duration-300 ease-in-out;
}
</style>