// Load environment variables
import dotenv from 'dotenv';
dotenv.config();

// Initialize Lucide Icons
lucide.createIcons();

// API Key (loaded from environment variable)
const API_KEY = process.env.API_KEY; 
const API_URL_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';
const MODEL_FLASH = 'gemini-2.5-flash-preview-05-20';

// Helper function for API calls with exponential backoff
async function fetchWithRetry(url, options, maxRetries = 5) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        } catch (error) {
            if (i === maxRetries - 1) throw error;
            const delay = Math.pow(2, i) * 1000 + Math.random() * 1000;
            await new Promise(resolve => setTimeout(resolve, delay));
        }
    }
}

// --- LLM Feature 1: Title Suggestion Logic (Hero Section) ---
const titleInput = document.getElementById('title-input');
const titleSuggestBtn = document.getElementById('title-suggest-btn');
const titleOutput = document.getElementById('title-suggestion-output');

titleSuggestBtn.addEventListener('click', async () => {
    const draftTitle = titleInput.value.trim();
    if (draftTitle.length < 10) {
        titleOutput.innerHTML = '<p class="text-yellow-400">Please enter a longer, more descriptive draft title (min 10 characters).</p>';
        return;
    }

    titleOutput.innerHTML = `
        <div class="flex items-center text-teal-300">
            <div class="flex space-x-1">
                <span class="loader-dot w-2 h-2 bg-teal-300 rounded-full"></span>
                <span class="loader-dot w-2 h-2 bg-teal-300 rounded-full"></span>
                <span class="loader-dot w-2 h-2 bg-teal-300 rounded-full"></span>
            </div>
            <span class="ml-2">Optimizing your title...</span>
        </div>
    `;
    
    const systemPrompt = `You are a professional academic editor. Your task is to take a draft research paper title and generate exactly three (3) highly professional, concise, and academically compelling alternatives. Use clear, formal language. Format the output as a numbered list in Markdown.`;
    const userQuery = `Draft Title: "${draftTitle}"`;
    
    const payload = {
        contents: [{ parts: [{ text: userQuery }] }],
        systemInstruction: { parts: [{ text: systemPrompt }] },
    };
    
    const url = `${API_URL_BASE}${MODEL_FLASH}:generateContent?key=${API_KEY}`;

    try {
        const result = await fetchWithRetry(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const text = result.candidates?.[0]?.content?.parts?.[0]?.text;
        if (text) {
            titleOutput.innerHTML = `
                <p class="text-teal-300 font-semibold mb-1">Suggested Titles:</p>
                <div class="bg-primary/50 p-3 rounded-lg">${text.replace(/\n/g, '<br>')}</div>
            `;
        } else {
            titleOutput.innerHTML = '<p class="text-red-400">Error: Could not generate suggestions. Please try again.</p>';
        }

    } catch (error) {
        console.error("LLM API Error:", error);
        titleOutput.innerHTML = '<p class="text-red-400">API failed. Check console for details.</p>';
    }
});

// --- LLM Feature 2: Policy Clarifier Bot (Static Placement) ---
const chatWindow = document.getElementById('clarifier-chat-window');
const chatInput = document.getElementById('clarifier-input');
const chatSendBtn = document.getElementById('clarifier-send-btn');

// Full context of guidelines for the bot (Policy Grounding)
const POLICY_CONTEXT = `
    The SIES Journal of Humanities is a peer-reviewed, multidisciplinary annual journal.
    - **Submission Fee:** There are NO submission or publication fees.
    - **Manuscript Length:** Articles must be between 3000 and 6000 words.
    - **Formatting:** Use MS Word, Times New Roman, font size 12, with 1.5 spacing.
    - **Citation Style:** Strictly follow the latest edition of the MLA (Modern Language Association) style.
    - **Ethics:** All research must comply with the SIESASCN Research Ethics Policy. Articles must be original, plagiarism-free, and authors must declare no conflict of interest.
    - **Review:** Double-blind peer review process. Authors must anonymize their documents (remove name, affiliation).
    - **Review Timeline:** Initial decision communicated within 6 to 8 weeks.
`;
    
function appendMessage(sender, text, isLoader = false) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('chat-bubble');
    
    if (isLoader) {
        messageDiv.classList.add('bot-message', 'flex', 'items-center');
        messageDiv.innerHTML = `
            <div class="flex space-x-1">
                <span class="loader-dot w-2 h-2 bg-primary rounded-full"></span>
                <span class="loader-dot w-2 h-2 bg-primary rounded-full"></span>
                <span class="loader-dot w-2 h-2 bg-primary rounded-full"></span>
            </div>
        `;
        messageDiv.id = 'loader-message';
    } else {
        messageDiv.textContent = text;
        if (sender === 'user') {
            messageDiv.classList.add('user-message');
        } else {
            messageDiv.classList.add('bot-message');
        }
    }
    
    chatWindow.appendChild(messageDiv);
    chatWindow.scrollTop = chatWindow.scrollHeight; // Auto-scroll
}

async function handleClarifierQuery() {
    const userQuery = chatInput.value.trim();
    if (!userQuery) return;
    
    appendMessage('user', userQuery);
    chatInput.value = '';
    chatInput.disabled = true;
    chatSendBtn.disabled = true;
    
    appendMessage('bot', '', true); // Show loader
    
    const systemPrompt = `You are the SIES Journal Policy Clarifier Bot. Your goal is to provide concise, direct, and accurate answers based ONLY on the provided context about the journal's policies. If the answer is not in the context, state that you cannot answer it and refer the user to the FAQ page.`;
    
    const contextQuery = `Context: ${POLICY_CONTEXT}\n\nUser Question: ${userQuery}`;

    const payload = {
        contents: [{ parts: [{ text: contextQuery }] }],
        systemInstruction: { parts: [{ text: systemPrompt }] },
    };
    
    const url = `${API_URL_BASE}${MODEL_FLASH}:generateContent?key=${API_KEY}`;

    try {
        const result = await fetchWithRetry(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const text = result.candidates?.[0]?.content?.parts?.[0]?.text || "Sorry, I couldn't process that question. Please try rephrasing.";
        
        // Remove loader and append bot response
        const loader = document.getElementById('loader-message');
        if (loader) loader.remove();
        
        appendMessage('bot', text);

    } catch (error) {
        console.error("LLM API Error:", error);
        const loader = document.getElementById('loader-message');
        if (loader) loader.remove();
        appendMessage('bot', 'A connection error occurred. Please try again later.');
    } finally {
        chatInput.disabled = false;
        chatSendBtn.disabled = false;
        chatInput.focus();
    }
}

chatSendBtn.addEventListener('click', handleClarifierQuery);

// --- Standard JS functions (Mobile Menu, Form, Modal, FAQ, Slider) ---

// --- Mobile Menu Toggle ---
const menuButton = document.getElementById('menu-button');
const mobileMenu = document.getElementById('mobile-menu');

menuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
});

// Close mobile menu when a link is clicked
mobileMenu.querySelectorAll('a, button').forEach(el => {
    el.addEventListener('click', () => {
        // Wait briefly for modal/scroll to start before hiding
        setTimeout(() => mobileMenu.classList.add('hidden'), 300); 
    });
});

// --- Simple Form Submission Handler ---
const submissionForm = document.getElementById('submission-form');
const formMessage = document.getElementById('form-message');

submissionForm.addEventListener('submit', function(e) {
    e.preventDefault();
    formMessage.textContent = 'Thank you! Your message has been received.';
    formMessage.classList.remove('hidden', 'text-red-600');
    formMessage.classList.add('text-green-600');
    submissionForm.reset();
    
    setTimeout(() => {
        formMessage.classList.add('hidden');
    }, 5000);
});

// --- Modal Logic ---
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('opacity-0', 'pointer-events-none');
    // Animate content for smooth transition
    modal.querySelector('div:first-child').classList.remove('translate-y-4');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    // Animate content back before hiding
    modal.querySelector('div:first-child').classList.add('translate-y-4');
    
    setTimeout(() => {
        modal.classList.add('opacity-0', 'pointer-events-none');
    }, 300);
}

// Close modal when clicking outside the content
document.getElementById('submissionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('submissionModal');
    }
});

// --- FAQ Accordion Logic
document.querySelectorAll('.faq-toggle').forEach(button => {
    button.addEventListener('click', () => {
        const item = button.closest('.faq-item');
        const content = item.querySelector('.faq-content');
        const icon = button.querySelector('i');
        const isExpanded = item.classList.contains('faq-open');

        // Close all other open items
        document.querySelectorAll('.faq-item.faq-open').forEach(openItem => {
            if (openItem !== item) {
                openItem.classList.remove('faq-open');
                openItem.querySelector('i').classList.remove('rotate-180');
            }
        });

        // Toggle current item
        if (isExpanded) {
            item.classList.remove('faq-open');
            icon.classList.remove('rotate-180');
        } else {
            item.classList.add('faq-open');
            icon.classList.add('rotate-180');
        }

        // Re-initialize Lucide icons for the newly rotated icon
        lucide.createIcons();
    });
});

// --- Testimonial Slider Logic ---
const slider = document.getElementById('testimonial-slider');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const dotsContainer = document.getElementById('slider-dots');
const slides = slider.children;
const totalSlides = slides.length;
let currentSlide = 0;

function createDots() {
    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('button');
        dot.classList.add('w-3', 'h-3', 'rounded-full', 'bg-white/50', 'hover:bg-accent', 'transition', 'duration-300');
        dot.setAttribute('data-slide', i);
        dot.onclick = () => showSlide(i);
        dotsContainer.appendChild(dot);
    }
}

function showSlide(index) {
    // Loop back if necessary
    if (index < 0) {
        index = totalSlides - 1;
    } else if (index >= totalSlides) {
        index = 0;
    }

    currentSlide = index;
    const offset = -currentSlide * 100;
    slider.style.transform = `translateX(${offset}%)`;

    // Update dots
    Array.from(dotsContainer.children).forEach((dot, i) => {
        dot.classList.remove('bg-accent', 'scale-110');
        dot.classList.add('bg-white/50');
        if (i === currentSlide) {
            dot.classList.add('bg-accent', 'scale-110');
            dot.classList.remove('bg-white/50');
        }
    });
}

prevBtn.addEventListener('click', () => {
    showSlide(currentSlide - 1);
});

nextBtn.addEventListener('click', () => {
    showSlide(currentSlide + 1);
});

createDots();
showSlide(0);