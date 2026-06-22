<div class="apply-modal" id="applyModal" hidden aria-hidden="true">
    <div class="apply-modal__backdrop" data-close-apply-modal></div>
    <div class="apply-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="applyModalTitle">
        <div class="apply-modal__header">
            <div>
                <p class="apply-modal__eyebrow" id="applyModalEyebrow">Easy Apply</p>
                <h2 id="applyModalTitle">Apply for job</h2>
                <p class="apply-modal__subtitle" id="applyModalSubtitle"></p>
            </div>
            <button type="button" class="apply-modal__close" data-close-apply-modal aria-label="Close apply form">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="applyModalForm" class="apply-modal__form" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="job_id" id="applyModalJobId" value="">
            <input type="hidden" name="application_id" id="applyModalApplicationId" value="">

            <label class="apply-field" for="applyCoverLetter">
                <span>Cover letter</span>
                <textarea
                    id="applyCoverLetter"
                    name="cover_letter"
                    rows="6"
                    maxlength="5000"
                    placeholder="Tell the employer why you are a great fit for this role..."
                ></textarea>
                <small>Optional · up to 5000 characters</small>
            </label>

            <div class="apply-field">
                <span>CV / Resume</span>
                <div class="apply-cv-box" id="applyCvBox">
                    <p class="apply-cv-empty" id="applyCvEmpty">No CV attached yet. Upload one below to apply.</p>
                    <div class="apply-cv-current" id="applyCvCurrent" hidden>
                        <div class="apply-cv-current__meta">
                            <strong id="applyCvFilename"></strong>
                            <span id="applyCvUpdated"></span>
                        </div>
                        <a href="#" id="applyCvViewLink" class="apply-cv-view" target="_blank" rel="noopener">View current CV</a>
                    </div>
                </div>
                <label class="apply-cv-upload">
                    <input type="file" name="cv" id="applyCvInput" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                    <span id="applyCvUploadLabel">Choose CV file</span>
                </label>
                <small>PDF or DOCX, max 5MB. Updates your profile CV for future applications too.</small>
                <p class="apply-cv-pending" id="applyCvPending" hidden></p>
            </div>

            <p class="apply-modal__error" id="applyModalError" hidden></p>

            <div class="apply-modal__actions">
                <button type="button" class="btn-view" data-close-apply-modal>Cancel</button>
                <button type="submit" class="btn-apply" id="applyModalSubmit">Submit application</button>
            </div>
        </form>
    </div>
</div>
