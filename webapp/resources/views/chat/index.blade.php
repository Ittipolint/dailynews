@extends('layouts.app')

@section('title', 'Chat AI — Graph RAG')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-chat-dots me-2"></i>ค้นหาข่าวย้อนหลังด้วย AI</h4>
    <div>
        <select id="locale" class="form-select form-select-sm d-inline-block w-auto">
            <option value="th">ไทย</option>
            <option value="en">English</option>
            <option value="zh">中文</option>
        </select>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div id="messages" class="mb-3" style="min-height: 400px; max-height: 500px; overflow-y: auto;">
            <div class="alert alert-light border mb-2">
                สวัสดีครับ! ถามข่าวย้อนหลังได้ เช่น <em>"มีข่าวเทคโนโลยีอะไรในสัปดาห์นี้"</em> หรือ <em>"ข่าวเกี่ยวกับ AI ล่าสุด"</em>
            </div>
        </div>
        <form id="chatForm" class="d-flex gap-2">
            <input type="text" id="question" class="form-control" placeholder="พิมพ์คำถาม..." required autocomplete="off">
            <button type="submit" class="btn btn-primary flex-shrink-0"><i class="bi bi-send me-1"></i>ส่ง</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const messagesEl = document.getElementById('messages');
const form = document.getElementById('chatForm');
const input = document.getElementById('question');
const locale = document.getElementById('locale');

function addMessage(text, isUser = false) {
    const div = document.createElement('div');
    div.className = isUser ? 'text-end mb-2' : 'text-start mb-2';
    const bubble = document.createElement('div');
    bubble.className = 'd-inline-block p-3 rounded ' + (isUser ? 'bg-primary text-white' : 'bg-light border');
    bubble.innerHTML = text.replace(/\n/g, '<br>');
    div.appendChild(bubble);
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
}

function addSources(sources) {
    if (!sources || !sources.length) return;
    const div = document.createElement('div');
    div.className = 'mb-2 ps-2 border-start border-3 border-secondary';
    div.innerHTML = '<div class="small text-secondary mb-1"><i class="bi bi-link-45deg"></i> แหล่งอ้างอิง:</div>';
    sources.slice(0, 5).forEach(s => {
        div.innerHTML += `<div class="small mb-1"><a href="${s.url}" target="_blank">${s.title}</a> <span class="text-secondary">(${s.source ?? 'unknown'})</span></div>`;
    });
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const question = input.value.trim();
    if (!question) return;

    addMessage(question, true);
    input.value = '';

    const typing = document.createElement('div');
    typing.className = 'text-start mb-2';
    typing.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังค้นหา...';
    messagesEl.appendChild(typing);

    try {
        const res = await fetch('{{ route('chat.ask') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ question, locale: locale.value }),
        });

        const data = await res.json();
        typing.remove();
        addMessage(data.answer ?? 'ไม่พบคำตอบ');
        addSources(data.sources);
    } catch (err) {
        typing.remove();
        addMessage('ขออภัย เกิดข้อผิดพลาด โปรดลองใหม่');
    }
});
</script>
@endpush
