<div class="interview-schedule-modal" id="interviewScheduleModal" hidden aria-hidden="true">
    <div class="interview-schedule-modal__backdrop" data-close-interview-modal></div>
    <div class="interview-schedule-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="interviewScheduleTitle">
        <div class="interview-schedule-modal__header">
            <div>
                <p class="interview-schedule-modal__eyebrow">Schedule interview</p>
                <h2 id="interviewScheduleTitle">Interview details</h2>
                <p class="interview-schedule-modal__subtitle">Send a reply and pick a date for the applicant.</p>
            </div>
            <button type="button" class="interview-schedule-modal__close" data-close-interview-modal aria-label="Close interview form">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="interviewScheduleForm" class="interview-schedule-modal__form" novalidate>
            <input type="hidden" name="application_id" id="interviewApplicationId" value="">

            <label class="interview-field" for="interviewReplyMessage">
                <span>Reply message</span>
                <textarea
                    id="interviewReplyMessage"
                    name="reply_message"
                    rows="5"
                    maxlength="2000"
                    placeholder="Share interview details, location, meeting link, or what to prepare..."
                    required
                ></textarea>
            </label>

            <label class="interview-field" for="interviewDate">
                <span>Interview date</span>
                <input type="date" id="interviewDate" name="interview_date" required>
            </label>

            <p class="interview-schedule-modal__error" id="interviewScheduleError" hidden></p>

            <div class="interview-schedule-modal__actions">
                <button type="button" class="btn-outline-full" data-close-interview-modal>Cancel</button>
                <button type="submit" class="btn-primary" id="interviewScheduleSubmit">Schedule interview</button>
            </div>
        </form>
    </div>
</div>
