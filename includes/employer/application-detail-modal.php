<div class="employer-app-modal" id="employerAppModal" hidden aria-hidden="true">
    <div class="employer-app-modal__backdrop" data-close-employer-app-modal></div>
    <div class="employer-app-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="employerAppModalTitle">
        <div class="employer-app-modal__header">
            <div>
                <p class="employer-app-modal__eyebrow">Application review</p>
                <h2 id="employerAppModalTitle">Applicant</h2>
                <p class="employer-app-modal__subtitle" id="employerAppModalSubtitle"></p>
            </div>
            <button type="button" class="employer-app-modal__close" data-close-employer-app-modal aria-label="Close application review">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="employer-app-modal__body" id="employerAppModalBody">
            <p class="employer-app-modal__loading">Loading application…</p>
        </div>

        <div class="employer-app-modal__footer" id="employerAppModalFooter" hidden>
            <label class="employer-app-modal__status-field">
                <span>Status</span>
                <select id="employerAppModalStatus" class="employer-app-modal__status-select"></select>
            </label>
            <div class="employer-app-modal__documents-field">
                <span>Documents</span>
                <div class="employer-app-modal__document-actions">
                    <a href="#" id="employerAppModalCvLink" class="employer-app-modal__btn employer-app-modal__btn--cv" target="_blank" rel="noopener" hidden>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        View CV
                        <svg class="employer-app-modal__btn-icon--external" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                    <span id="employerAppModalNoCv" class="employer-app-modal__no-cv" hidden>No CV uploaded</span>
                </div>
            </div>
        </div>
    </div>
</div>
