import { Editor } from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';
import '@toast-ui/editor/dist/theme/toastui-editor-dark.css';
import colorSyntax from '@toast-ui/editor-plugin-color-syntax';
import '@toast-ui/editor-plugin-color-syntax/dist/toastui-editor-plugin-color-syntax.css';

class PostEditor {
    constructor() {
        this.editor = null;
        this.init();
    }

    init() {
        // 에디터를 적용할 요소
        const editorContainer = document.getElementById('editor');
        if (!editorContainer) return;

        this.editor = new Editor({
            el: editorContainer,
            height: '500px', // 에디터 높이
            initialEditType: 'markdown', // 초기 입력 타입 (markdown/wysiwyg)
            previewStyle: 'vertical', // 미리보기 스타일
            plugins: [colorSyntax],
            hooks: {
                addImageBlobHook: (blob, callback) => {
                    this.uploadImage(blob, callback);
                }
            },
            toolbarItems: [
                ['heading', 'bold', 'italic', 'strike'],
                ['hr', 'quote'],
                ['ul', 'ol', 'task', 'indent', 'outdent'],
                ['table', 'image', 'link'],
                ['code', 'codeblock'],
                ['scrollSync'],
            ]
        });

        // 폼 제출 시 에디터 내용을 textarea에 설정
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                const contentTextarea = document.getElementById('content');
                if (contentTextarea && this.editor) {
                    contentTextarea.value = this.editor.getMarkdown();
                }
            });
        }

        // 초기 내용 설정 (수정 모드일 때)
        const initialContent = document.getElementById('content').value;
        if (initialContent && this.editor) {
            this.editor.setMarkdown(initialContent);
        }
    }

    uploadImage(blob, callback) {
        const formData = new FormData();
        formData.append('image', blob);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        fetch('/admin/posts/upload-image', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    callback(data.url, data.alt || 'image');
                } else {
                    alert('이미지 업로드에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('이미지 업로드 오류:', error);
                alert('이미지 업로드 중 오류가 발생했습니다.');
            });
    }
}

// DOM이 로드되면 에디터 초기화
document.addEventListener('DOMContentLoaded', () => {
    new PostEditor();
});
