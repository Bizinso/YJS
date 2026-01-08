<script setup>
import { computed, ref } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({
    block: { type: Object, required: true }
})

const emit = defineEmits(['update:data', 'update:settings'])

const data = computed(() => props.block.data || {})
const settings = computed(() => props.block.settings || {})

const editorContent = ref(data.value.content || '')

const toolbarOptions = [
    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
    [{ 'align': [] }],
    ['link', 'image'],
    ['blockquote', 'code-block'],
    ['clean']
]

const updateContent = (content) => {
    emit('update:data', { content })
}

const updateSettings = (key, value) => {
    emit('update:settings', { [key]: value })
}
</script>

<template>
    <div class="space-y-4">
        <!-- Rich Text Editor -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
            <div class="border rounded-lg overflow-hidden">
                <QuillEditor
                    v-model:content="editorContent"
                    content-type="html"
                    :toolbar="toolbarOptions"
                    theme="snow"
                    @update:content="updateContent"
                    class="min-h-[300px]"
                />
            </div>
        </div>

        <!-- Settings -->
        <div class="pt-4 border-t space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Width</label>
                <input
                    type="text"
                    :value="settings.max_width"
                    @input="updateSettings('max_width', $event.target.value)"
                    placeholder="800px"
                    class="w-full px-3 py-2 border rounded-lg text-sm"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Text Alignment</label>
                <select
                    :value="settings.text_align"
                    @change="updateSettings('text_align', $event.target.value)"
                    class="w-full px-3 py-2 border rounded-lg text-sm"
                >
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                    <option value="justify">Justify</option>
                </select>
            </div>
        </div>
    </div>
</template>

<style>
.ql-container {
    min-height: 250px;
    font-size: 14px;
}
.ql-editor {
    min-height: 250px;
}
</style>
