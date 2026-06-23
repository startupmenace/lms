<div id="chatbot" class="fixed bottom-6 right-6 z-50" role="complementary" aria-label="Help chatbot">
    <button id="chatbot-toggle" class="w-14 h-14 rounded-full bg-gradient-to-r from-teal-500 to-teal-600 text-white shadow-xl hover:shadow-teal-200/50 hover:scale-105 transition-all flex items-center justify-center relative" aria-label="Toggle help chat" aria-expanded="false">
        <i id="chatbot-icon" class="fas fa-comment-dots text-2xl"></i>
        <span class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 border-2 border-white rounded-full animate-pulse"></span>
    </button>
    <div id="chatbot-panel" class="hidden absolute bottom-16 right-0 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden" role="dialog" aria-label="Help chat">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-robot text-white"></i>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm">Jewel Assistant</p>
                    <p class="text-teal-100 text-xs">Here to help you</p>
                </div>
            </div>
            <button id="chatbot-close" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/80 hover:bg-white/20 hover:text-white transition" aria-label="Close chat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="chatbot-messages" class="h-80 overflow-y-auto p-4 space-y-3 bg-gray-50" role="log" aria-live="polite">
            <div class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-robot text-xs text-teal-600"></i>
                </div>
                <div class="bg-white rounded-2xl rounded-tl-none px-4 py-2.5 shadow-sm border border-gray-100 max-w-[85%]">
                    <p class="text-sm text-gray-700">Hi there! I'm your teaching assistant. How can I help you today?</p>
                </div>
            </div>
        </div>
        <div class="p-3 border-t border-gray-200 bg-white">
            <div id="chatbot-suggestions" class="flex flex-wrap gap-1.5 mb-3">
                <button class="text-xs px-3 py-1.5 rounded-full bg-teal-50 text-teal-700 hover:bg-teal-100 transition font-medium" data-query="mark attendance">Mark Attendance</button>
                <button class="text-xs px-3 py-1.5 rounded-full bg-teal-50 text-teal-700 hover:bg-teal-100 transition font-medium" data-query="create test">Create Test</button>
                <button class="text-xs px-3 py-1.5 rounded-full bg-teal-50 text-teal-700 hover:bg-teal-100 transition font-medium" data-query="schedule exam">Schedule Exam</button>
                <button class="text-xs px-3 py-1.5 rounded-full bg-teal-50 text-teal-700 hover:bg-teal-100 transition font-medium" data-query="grade submissions">Grade Work</button>
                <button class="text-xs px-3 py-1.5 rounded-full bg-teal-50 text-teal-700 hover:bg-teal-100 transition font-medium" data-query="live class">Live Class</button>
            </div>
            <div class="flex gap-2">
                <input id="chatbot-input" type="text" class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none" placeholder="Ask me anything..." aria-label="Ask a question">
                <button id="chatbot-send" class="w-10 h-10 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition flex items-center justify-center flex-shrink-0" aria-label="Send">
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var knowledge = {
        "mark attendance": "To mark attendance, go to <strong>Attendance</strong> in the sidebar. Select a class and date, then mark each student as <strong>Present</strong>, <strong>Absent</strong>, <strong>Late</strong>, or <strong>Leave</strong>. Click <strong>Save Attendance</strong> when done.",
        "create test": "To create a test:<br>1. Go to <strong>Tests</strong> in the sidebar<br>2. Click <strong>Create New Test</strong><br>3. Enter title, class, subject, and duration<br>4. Click <strong>Save</strong>, then <strong>Add Questions</strong><br>5. Add MCQ and/or subjective questions with marks per question",
        "schedule exam": "To schedule an exam:<br>1. Go to <strong>Exam Planner</strong><br>2. Click <strong>Schedule Exam</strong><br>3. Enter the exam title, select class<br>4. Set date, start time, and end time<br>5. Add subjects with max/pass marks<br>6. Click <strong>Save</strong>",
        "grade submissions": "To grade student submissions:<br>1. Go to <strong>Evaluation</strong> in the sidebar<br>2. You'll see a list of submitted tests<br>3. Click <strong>Grade</strong> on any submission<br>4. Review answers and assign marks<br>5. Add feedback comments<br>6. Click <strong>Submit Grade</strong>",
        "grade": "To grade student submissions:<br>1. Go to <strong>Evaluation</strong> in the sidebar<br>2. You'll see a list of submitted tests<br>3. Click <strong>Grade</strong> on any submission<br>4. Review answers and assign marks<br>5. Add feedback comments<br>6. Click <strong>Submit Grade</strong>",
        "live class": "To start a live class:<br>1. Go to <strong>Live Class</strong> in the sidebar<br>2. Click <strong>Schedule New Class</strong><br>3. Enter title, select class/subject<br>4. Set date, time, and duration<br>5. Click <strong>Schedule</strong><br>6. At class time, click <strong>Join</strong> to launch the virtual classroom",
        "add student": "To add a new student:<br>1. Go to <strong>Students</strong> in the sidebar<br>2. Click <strong>Add New Student</strong><br>3. Enter enrollment ID, student name, class<br>4. Fill in parent/guardian contact details<br>5. Click <strong>Save</strong>",
        "fee": "To manage fees:<br>1. Go to <strong>Fee Management</strong><br>2. Click <strong>Create Fee Structure</strong><br>3. Give it a name, select classes, set frequency<br>4. Add fee categories (tuition, transport, etc.)<br>5. Students can pay via M-Pesa or bank transfer",
        "chat": "To use the chat system:<br>1. Go to <strong>Chat</strong> in the sidebar<br>2. Click on a contact from the left panel<br>3. Type your message in the input box<br>4. Press <strong>Enter</strong> or click send<br>5. You can chat with students and other teachers",
        "notice": "To post a notice:<br>1. Go to <strong>Notice Board</strong><br>2. Click <strong>Add Notice</strong><br>3. Enter a title and content<br>4. Choose target audience (All, Teachers, Students)<br>5. Optionally select a specific class<br>6. Click <strong>Publish</strong>",
        "attendance overview": "The attendance chart on your dashboard shows daily attendance trends. You can see <strong>Present</strong> vs <strong>Absent</strong> counts for the week. For detailed records, go to the <strong>Attendance</strong> module.",
        "hello": "Hello! How can I assist you with your teaching today?",
        "hi": "Hi there! What would you like help with?",
        "help": "I can help you with:<br>• Marking attendance<br>• Creating tests and exams<br>• Grading submissions<br>• Starting live classes<br>• Adding students<br>• Managing fees<br>• Using chat and notices<br><br>Just type a question or click a suggestion above!"
    };

    var btn = document.getElementById('chatbot-toggle');
    var panel = document.getElementById('chatbot-panel');
    var close = document.getElementById('chatbot-close');
    var messages = document.getElementById('chatbot-messages');
    var input = document.getElementById('chatbot-input');
    var send = document.getElementById('chatbot-send');
    var icon = document.getElementById('chatbot-icon');
    var suggestions = document.querySelectorAll('[data-query]');

    function toggleChat() {
        var isOpen = panel.classList.contains('hidden');
        panel.classList.toggle('hidden');
        btn.setAttribute('aria-expanded', isOpen);
        icon.className = isOpen ? 'fas fa-times text-2xl' : 'fas fa-comment-dots text-2xl';
        if (isOpen) setTimeout(function() { input.focus(); }, 300);
    }

    function addMessage(text, isUser) {
        var div = document.createElement('div');
        div.className = 'flex items-start gap-2.5 ' + (isUser ? 'justify-end' : '');
        if (isUser) {
            div.innerHTML = '<div class="bg-teal-600 text-white rounded-2xl rounded-tr-none px-4 py-2.5 max-w-[85%]"><p class="text-sm">' + text + '</p></div><div class="w-7 h-7 rounded-full bg-teal-600 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-user text-xs text-white"></i></div>';
        } else {
            div.innerHTML = '<div class="w-7 h-7 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-robot text-xs text-teal-600"></i></div><div class="bg-white rounded-2xl rounded-tl-none px-4 py-2.5 shadow-sm border border-gray-100 max-w-[85%]"><p class="text-sm text-gray-700">' + text + '</p></div>';
        }
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function findAnswer(query) {
        var q = query.toLowerCase().trim();
        for (var key in knowledge) {
            if (q.includes(key)) return knowledge[key];
        }
        if (q.includes('how') && q.includes('attendance')) return knowledge["mark attendance"];
        if (q.includes('how') && q.includes('test')) return knowledge["create test"];
        if (q.includes('how') && q.includes('exam')) return knowledge["schedule exam"];
        if (q.includes('how') && q.includes('grade')) return knowledge["grade submissions"];
        if (q.includes('how') && q.includes('live')) return knowledge["live class"];
        if (q.includes('how') && q.includes('student')) return knowledge["add student"];
        if (q.includes('thank')) return "You're welcome! Feel free to ask if you need anything else.";
        if (q.includes('yes') || q.includes('ok')) return "Great! Just let me know what you need help with.";
        return "I'm not sure about that. Try one of the suggestions above, or ask about:<br>• Attendance<br>• Tests<br>• Exams<br>• Grading<br>• Live classes<br>• Students<br>• Fees";
    }

    function handleSend() {
        var text = input.value.trim();
        if (!text) return;
        addMessage(text, true);
        input.value = '';
        setTimeout(function() {
            var answer = findAnswer(text);
            addMessage(answer, false);
        }, 400);
    }

    if (btn) btn.addEventListener('click', toggleChat);
    if (close) close.addEventListener('click', toggleChat);
    if (send) send.addEventListener('click', handleSend);
    if (input) input.addEventListener('keydown', function(e) { if (e.key === 'Enter') handleSend(); });
    suggestions.forEach(function(el) {
        el.addEventListener('click', function() {
            var query = this.getAttribute('data-query');
            if (query) {
                addMessage(query, true);
                setTimeout(function() {
                    var answer = findAnswer(query);
                    addMessage(answer, false);
                }, 300);
            }
        });
    });

    document.addEventListener('click', function(e) {
        var chatbot = document.getElementById('chatbot');
        if (chatbot && !chatbot.contains(e.target) && !panel.classList.contains('hidden')) {
            panel.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
            icon.className = 'fas fa-comment-dots text-2xl';
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
            panel.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
            icon.className = 'fas fa-comment-dots text-2xl';
        }
    });
})();
</script>
