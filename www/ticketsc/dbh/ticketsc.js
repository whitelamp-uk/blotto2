const TicketWidget = {
    slugify(label) {
        return label
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .trim()
            .replace(/\s+/g, '_');
    },


    injectCustomFont(url) {
        if (!url) {
            return;
        }

        // Replace raw spaces with %20
        const safeUrl = url.replace(/ /g, '%20');

        this.fontName = this.extractFontNameFromUrl(safeUrl);
        const fontFaceStyle = document.createElement('style');
        fontFaceStyle.textContent = `
            @font-face {
                font-family: '${this.fontName}';
                src: url('${safeUrl}') format('opentype');
                font-weight: normal;
                font-style: normal;
            }
            body, input, select, textarea, button, .ticket-widget-wrapper {
                font-family: '${this.fontName}', system-ui, sans-serif !important;
            }
        `;
        document.head.appendChild(fontFaceStyle);
    },

    extractFontNameFromUrl(fontUrl) {
        try {
            if (!fontUrl) {
                console.warn('No font URL provided, using fallback.');
                return 'DefaultFont';
            }
            const parsedUrl = new URL(fontUrl);
            const path = parsedUrl.pathname;
            const fileName = path.split('/').pop(); // e.g. Roc Grotesk Regular.otf
            const nameWithoutExt = fileName.replace(/\.[^/.]+$/, ''); // Remove .otf/.ttf/etc
            const cleaned = nameWithoutExt.replace(/[%20]+/g, ' ').replace(/[^a-zA-Z0-9 _-]/g, '').trim();

            // Optionally: convert spaces to underscores or camelCase if needed
            return cleaned || 'CustomFont';
        } catch (e) {
            console.warn('Invalid font URL, fallback used.');
            return 'CustomFont';
        }
    },

    async init(options) {

        this.options = options || {};

        // raffleMode: when true, switch the UI into “raffle” logic
        this.raffleMode = !!options.raffleMode;

        const pricePerDraw = options.ticketPrice;
        const maxPurchase = options.maxPurchase;
        const quantities = options.quantities;
        const weekOptions = options.weekOptions;
        const nextDrawDate = options.nextDrawDate;
        const nextDrawDateRaw = options.nextDrawDateRaw;

        this.signupUrlPrivacy = options.signupUrlPrivacy;
        this.signupUrlTerms = options.signupUrlTerms;

        this.clientCode = options?.clientCode ?? null;

        if (!this.clientCode) {
            console.error('No client code set in lottery form!');
            return;
        }

        if (options.customFontUrl) {
            this.injectCustomFont(options.customFontUrl);
        }

        // Error mapping for this form
        const errorToField = (errorMsg) => {
            if (errorMsg.includes('not verified')) return 'mobile';
            if (errorMsg.includes('is not a valid phone number')) return 'telephone';
            // Add more mappings as needed
            return null;
        };

        // Update the terms & privacy links in the form
        // const termsLabel = document.querySelector('label[for="terms"]');
        // if (termsLabel) {
        //     termsLabel.innerHTML = `
        //         I accept the 
        //         <a href="${this.signupUrlTerms}" target="_blank" rel="noopener noreferrer">terms &amp; conditions</a> 
        //         and 
        //         <a href="${this.signupUrlPrivacy}" target="_blank" rel="noopener noreferrer">privacy policy</a>.
        //         <span class="text-danger">*</span>
        //     `;
        // }
        const customTermsMessage = options?.customTermsMessage ?? null;
        //console.log('customTermsMessage: ', customTermsMessage);
        const termsLabel = document.querySelector('label[for="terms"]');
        if (termsLabel) {
            if (customTermsMessage) {
                // Use custom message directly (assumed to include any links)
                termsLabel.innerHTML = customTermsMessage;
            } else {
                // Use default message with URLs injected
                termsLabel.innerHTML = `
                        I accept the 
                        <a href="${this.signupUrlTerms}" target="_blank" rel="noopener noreferrer">terms &amp; conditions</a> 
                        and 
                        <a href="${this.signupUrlPrivacy}" target="_blank" rel="noopener noreferrer">privacy policy</a>.
                        <span class="text-danger">*</span>
                        `;
            }
        }

        this.buttonId = options?.buttonId || null;
        this.customFont = options?.customFont || null;
        this.disableResize = options?.disableResize || false;

        this.customerColor = options?.customerColor || '#000';
        this.customerColor1 = options?.customerColor1 || '#000';
        this.customerColor2 = options?.customerColor2 || '#000';

        this.titles = options?.titles || ['Mrs', 'Mr', 'Miss', 'Ms', 'Mx', 'Dr', 'Prof', 'Rev', 'Sir', 'Dame', 'Lady'];

        this.contactPhone = options?.contactPhone || '';
        this.contactEmail = options?.contactEmail || '';

        this.selfExclusionEmail = options?.selfExclusionEmail || '';

        this.referenceBoxesSVG = this.generateReferenceBoxesSVG();
        const refG = document.querySelector('#reference-boxes-svg');
        if (refG) {
            refG.innerHTML = this.referenceBoxesSVG;
        }

        this.customWidth = options?.customWidth || 'auto';
        this.marginTop = options?.marginTop || '2px';
        this.marginBottom = options?.marginBottom || '2px';

        this.otherComment = options?.otherComment;
        if (this?.otherComment?.length) {
            const prefP = document.getElementById('preference-p');
            if (prefP) {
                prefP.textContent = this.otherComment;
            }
        }

        this.directDebitSVG = `<img src="https://flame.thefundraisingfoundry.com/storage/dd_logo.svg" alt="Direct Debit" width="150" height="60">`;

        this.currentStep = 0;
        // this.loadForm();
        // this.applyButtonStylesFromOptions(options);
        this.applyHostMarginsFromOptions(options);

        let chosenFont = this.customFont && typeof this.customFont === 'string' ?
            this.customFont :
            window.getComputedStyle(document.body).fontFamily || 'system-ui, sans-serif';

        document.documentElement.style.setProperty('--host-font-family', chosenFont);
        document.documentElement.style.setProperty('--customer-card-header-bg', this.customerColor);
        document.documentElement.style.setProperty('--customer-color-1', this.customerColor1);
        document.documentElement.style.setProperty('--customer-color-2', this.customerColor2);

        // if options.customSteps is provided, rebuild UI
        if (options.customSteps) {
            // SNAPSHOT THE *ORIGINAL* PANELS ONCE
            this._originalPanels = Array
                .from(document.querySelectorAll('.step-content[data-step-content]'))
                .sort((a, b) => +a.dataset.stepContent - +b.dataset.stepContent)
                .map(el => el.cloneNode(true));

            this.buildWizard(options.customSteps);
        } else {
            console.log('Need to define custom steps!');
        }

        if (!this.disableResize) {
            // If auto-init is desired, it is now triggered by waiting for the element.
            setTimeout(() => {
                window.parent.postMessage({
                    type: 'resize',
                    height: this.getHeight(),
                }, '*');
            }, 100);
        }

        // Inline flatpickr CSS into shadow DOM
        fetch('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css')
            .then(res => res.text())
            .then(css => {
                const style = document.createElement('style');
                style.textContent = css;
                document.documentElement.appendChild(style);
            });


        this.prepareFlatpickr();

        const mobileInput = document.querySelector('#mobile');
        const mobileErrorDisplay = document.querySelector('#noticeMobile');

        if (mobileInput) {
            mobileInput.addEventListener('input', (e) => {
                const {
                    value,
                    error
                } = this.sanitizeAndValidateMobile(e.target);
                if (mobileErrorDisplay) {
                    mobileErrorDisplay.textContent = error || '';
                }
            });
        }

        const landlineInput = document.querySelector('#telephone');
        const landlineErrorDisplay = document.querySelector('#noticeLandline');

        if (landlineInput) {
            landlineInput.addEventListener('input', (e) => {
                const {
                    value,
                    error
                } = this.sanitizeAndValidateLandline(e.target); // This uses the landline validation
                if (landlineErrorDisplay) {
                    landlineErrorDisplay.textContent = error || '';
                }
            });
        }

        // Enforce sort code to the format xx-xx-xx.
        const sortCodeInput = document.querySelector('#sort_code');
        if (sortCodeInput) {
            sortCodeInput.addEventListener('input', function () {
                // Remove everything except digits.
                let digits = sortCodeInput.value.replace(/\D/g, '');
                // Limit to 6 digits
                if (digits.length > 6) {
                    digits = digits.slice(0, 6);
                }
                // Format to "xx-xx-xx"
                let formatted = '';
                if (digits.length > 0) {
                    formatted = digits.slice(0, 2);
                    if (digits.length >= 3) {
                        formatted += '-' + digits.slice(2, 4);
                    }
                    if (digits.length >= 5) {
                        formatted += '-' + digits.slice(4, 6);
                    }
                }
                sortCodeInput.value = formatted;
            });
        }

        const accountInput = document.querySelector('#account_number');
        if (accountInput) {
            accountInput.addEventListener('input', function () {
                // Remove all non-digit characters
                let digits = accountInput.value.replace(/\D/g, '');

                // Limit to 8 digits
                if (digits.length > 8) {
                    digits = digits.slice(0, 8);
                }

                // Set the cleaned value
                accountInput.value = digits;
            });
        }

        this.titleOptions = this.titles
            .map((title) => `<option value="${title}">${title}</option>`)
            .join('');

        // disable the code‐entry box until after the SMS is sent
        document.getElementById('mobile_verify').disabled = true;

        const verify_button_mobile = document.getElementById('verify_button_mobile');
        if (verify_button_mobile) {
            verify_button_mobile.addEventListener('click', async () => {
                const verifyInput = document.getElementById('mobile_verify');
                const notice = document.getElementById('noticeMobileVerify');
                const mobileInput = document.getElementById('mobile');
                const nonce = '';   //document.getElementById('nonce_mobile').value;

                const mobile = mobileInput.value.trim();
                if (!mobile) {
                    notice.textContent = "Enter your mobile number first.";
                    notice.style.display = 'block';
                    return;
                }

                // Disable the button and show loading
                verify_button_mobile.disabled = true;
                verify_button_mobile.textContent = "Sending...";

                try {
                    const response = await fetch("ticketsc.php?verify", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            mobile: mobile,
                            nonce: nonce
                        })
                    });

                    const data = await response.json();

                    if (data.result) {
                        // SMS sent, prompt for code
                        this.smsSent = true;
                        this.lastSentMobile = mobile;
                        verifyInput.disabled = false;
                        notice.textContent = "Code sent! Check your phone.";
                        notice.style.display = 'block';
                        // Keep button disabled (prevent re-send unless mobile changes)
                        verify_button_mobile.disabled = true;
                    } else {
                        this.smsSent = false;
                        notice.textContent = data.e || "Failed to send SMS.";
                        notice.style.display = 'block';
                        verify_button_mobile.disabled = false;
                    }
                } catch (err) {
                    this.smsSent = false;
                    notice.textContent = "Error sending SMS.";
                    notice.style.display = 'block';
                    verify_button_mobile.disabled = false;
                } finally {
                    verify_button_mobile.textContent = "Send SMS";
                }
            });

            // --- Reset SMS state if user changes the mobile number ---
            const mobileInput = document.getElementById('mobile');
            if (mobileInput) {
                mobileInput.addEventListener('input', () => {
                    // Only reset if value changes from what we sent to
                    if (mobileInput.value.trim() !== this.lastSentMobile) {
                        this.smsSent = false;
                        this.lastSentMobile = '';
                        verify_button_mobile.disabled = false;
                        document.getElementById('mobile_verify').disabled = true;
                    }
                });
            }
        }
    },
    /**
     * Prepare & load Flatpickr: 
     * 1) build the config 
     * 2) inject CSS + script
     */
    prepareFlatpickr() {
        // ── 1) Compute & stash Flatpickr config ──
        const today = new Date();
        const eighteen = new Date(
            today.getFullYear() - 18,
            today.getMonth(),
            today.getDate()
        );
        const dd = String(eighteen.getDate()).padStart(2, '0');
        const mm = String(eighteen.getMonth() + 1).padStart(2, '0');
        const yyyy = eighteen.getFullYear();
        this._flatpickrConfig = {
            dateFormat: 'd-m-Y',
            maxDate: `${dd}-${mm}-${yyyy}`,
            allowInput: true,
            altInput: true,
            altFormat: 'd-m-Y',
            appendTo: document.documentElement,
            disableMobile: false,
            yearSelector: true,
            defaultDate: '01-01-2000',
            minDate: '01-01-1900',
            onReady: (dates, str, inst) => {
                inst._input.classList.remove('flatpickr-hidden');
                if (this.fontName) {
                    const cls = this.fontName.replace(/\s+/g, '-');
                    inst._input.classList.add(`${cls}-flatpickr`);
                }
            }
        };

        // ── 2) Load Flatpickr library and CSS ──
        fetch('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css')
            .then(r => r.text())
            .then(css => {
                const style = document.createElement('style');
                style.textContent = css;
                document.head.appendChild(style);
            });

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
        script.onload = () => this.setupDatepicker(); // initial hookup
        document.head.appendChild(script);
    },
    setupDatepicker() {
        if (!window.flatpickr) {
            return; // not loaded yet?
        }
        const dateInput = document.querySelector('#dob');
        if (!dateInput) {
            return; // no DOB field in this layout?
        }
        // re-attach a fresh flatpickr instance
        flatpickr(dateInput, this._flatpickrConfig);
    },

    /**
     * Remaps the wizard UI to `count` steps,
     * grouping original `.step-content` panels as specified,
     * removes any inner step-navs, then adds one nav per step,
     * and re-initializes all widget behaviors.
     *
     * @param {{ count: number, steps: Array<{panels: number[], title: string, subtitle: string}> }} cfg
     */
    buildWizard(cfg) {
        // 1) Grab & clone all panels by their original index
        // const originals = Array.from(
        //     document.querySelectorAll('.step-content[data-step-content]')
        // )
        //     .sort((a, b) => +a.dataset.stepContent - +b.dataset.stepContent)
        //     .map(el => el.cloneNode(true));
        const originals = this._originalPanels;

        // 2) Render the stepper (or hide if only one)
        const stepper = document.getElementById('stepper');
        if (cfg.count === 1) {
            stepper.style.display = 'none';
        } else {
            stepper.style.display = '';
            stepper.innerHTML = '';
            cfg.steps.forEach((s, i) => {
                const div = document.createElement('div');
                div.className = 'step';
                div.dataset.step = i;
                div.innerHTML = `
                        <div class="circle">${i + 1}</div>
                        <div class="label">${s.title}</div>
                        <div class="sublabel">${s.subtitle}</div>
                    `;
                stepper.appendChild(div);
            });
        }

        // 3) Re-group panels into the steps-container
        const container = document.getElementById('steps-container');
        container.innerHTML = '';
        cfg.steps.forEach((s, i) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'step-content';
            wrapper.dataset.stepContent = i;

            // pull in each original panel’s innerHTML, sans its own nav
            wrapper.innerHTML = s.panels
                .map(idx => {
                    const panel = originals[idx].cloneNode(true);
                    panel.querySelectorAll('.step-nav').forEach(nav => nav.remove());
                    return panel.innerHTML;
                })
                .join('');

            // add a single, unified prev/next bar
            const nav = document.createElement('div');
            nav.className = 'step-nav';
            if (i > 0) {
                const prev = document.createElement('button');
                prev.type = 'button';
                prev.id = `prevBtn${i}`;
                prev.className = 'btn btn-secondary';
                prev.textContent = 'Previous';
                nav.appendChild(prev);
            }
            if (i < cfg.steps.length - 1) {
                const next = document.createElement('button');
                next.type = 'button';
                next.id = `nextBtn${i}`;
                next.className = 'btn btn-primary';
                next.textContent = 'Next';
                nav.appendChild(next);
            }
            wrapper.appendChild(nav);
            container.appendChild(wrapper);
        });

        // 4) Re-apply every piece of widget behavior in order:
        // this.applyStyles(); // host styling & sizing
        // this.renderTicketStep(true); // rebuild qty/week/cost UI - reversed Sian email requirement.
        // this.setupStepper(); // re-hook next/prev & validation
        // this.setupFindAddress(); // re-hook postcode lookup
        // this.setupPaymentToggle(); // wire up your Pay button
        // this.setupSubmit(); // wire up form.submit handler
        // this.setupDatepicker();

        // 4) Re-apply every piece of widget behavior in order — after layout
        requestAnimationFrame(() => {
        this.applyStyles();                 // host styling & sizing
        this.renderTicketStep(true);        // builds qty/week using window.innerWidth
        this.setupStepper();                // next/prev & validation
        this.setupFindAddress();            // postcode lookup
        this.setupPaymentToggle();          // Pay button
        this.setupSubmit();                 // form.submit
        this.setupDatepicker();             // DOB

        // Safari: run one more tick after paint so innerWidth/iframe size is final
        setTimeout(() => {
            this.renderTicketStep(true);
            window.parent.postMessage({ type: 'resize', height: this.getHeight() + 30 }, '*');
        }, 0);
        });

        // attach a one-time resize handler to keep radios correct on rotation/resize
        if (!this._boundResize) {
        this._boundResize = true;
        this._resizeDebounce = null;
        window.addEventListener('resize', () => {
            clearTimeout(this._resizeDebounce);
            this._resizeDebounce = setTimeout(() => {
            // only rebuild if step 2 exists and is in the DOM
            const qtyContainer = document.getElementById('quantityRadios');
            if (qtyContainer) {
                this.renderTicketStep(true);
                window.parent.postMessage({ type: 'resize', height: this.getHeight() + 30 }, '*');
            }
            }, 150);
        });
        }

    },

    getHeight() {
        // grab the single wrapper that’s currently visible
        const active = document.querySelector('.step-content.active');
        if (!active) {
            return 600;
        }

        // get its distance from the top of the document
        const {
            top
        } = active.getBoundingClientRect();

        // use scrollHeight so that all of its children count, even if they grow dynamically
        const contentHeight = active.scrollHeight;

        // add a little extra for padding/buffer
        const buffer = 60;

        return Math.ceil(top + contentHeight + buffer);
    },

    showStep(stepIndex) {
        const steps = Array.from(document.querySelectorAll('.step'));
        const panels = Array.from(document.querySelectorAll('.step-content'));
        const isFinal = stepIndex === panels.length - 1;
        const stepper = document.getElementById('stepper');

        // 1) Toggle the circles
        steps.forEach((step, idx) => {
            step.classList.toggle('active', idx === stepIndex);
        });

        // 2) Toggle the panels
        panels.forEach((panel, idx) => {
            panel.classList.toggle('active', idx === stepIndex);
        });

        // 3) Hide the stepper on the Thank-you slide
        stepper.style.display = isFinal ? 'none' : 'flex';

        // 4) Remember where we are
        this.currentStep = stepIndex;

        // Re-apply SMS sent state on DOM re-render
        if (this.smsSent && document.getElementById('mobile').value.trim() === this.lastSentMobile) {
            document.getElementById('verify_button_mobile').disabled = true;
            document.getElementById('mobile_verify').disabled = false;
        }

        // Inject self-exclusion email only when step 4 becomes visible
        const selfExclusionPara = document.querySelector('#selfExclusionPara');
        if (selfExclusionPara) {
            waitForElement('#selfExclusionPara', (selfExclusion) => {

                if (selfExclusion && selfExclusion.innerHTML.trim() === '') {
                    selfExclusion.innerHTML =
                        'To self-exclude, please email: <a href="mailto:' +
                        this.selfExclusionEmail +
                        '">' + this.selfExclusionEmail + '</a>.';
                }
            });

            const gdprContainer = document.querySelector('#gdprStatementContainer');
            if (gdprContainer) {
                gdprContainer.innerHTML =
                    '<p>' +
                    "Your support makes our work possible. We'd love to keep in touch with you to tell you more " +
                    'about our work and how you can support it. ' +
                    "We'll do this by the options you chose above and you can change these preferences at any " +
                    'time by calling or e-mailing us on ' +
                    '<strong>' + this.contactPhone + '</strong> or <a href="mailto:' + this.contactEmail + '">' + this.contactEmail + '</a>.' +
                    '</p>' +
                    '<p>We will never sell your details on to anyone else.</p>';
            }

        }

        requestAnimationFrame(() => {
            const newHeight = this.getHeight() + 30;
            window.parent.postMessage({
                type: 'resize',
                height: newHeight,
                scroll: true
            }, '*');
        });
    },
    setupStepper() {
        const steps = Array.from(document.querySelectorAll('.step'));
        const stepContents = Array.from(document.querySelectorAll('.step-content'));

        const validateCurrentStep = async () => {
            const current = stepContents[this.currentStep];
            let valid = true;

            // find out if this step contains a telephone field
            const stepDef = this.options.customSteps.steps[this.currentStep];
            // original panel index for 'Contact' is 1, but we detect by presence of #telephone
            const isContactStep = !!current.querySelector('#telephone');

            const isPostcodeStep = !!current.querySelector('#postcode');
            let postcodeValid = true;

            // Handle postcode validation if needed
            if (isPostcodeStep) {
                const postcodeInput = current.querySelector('#postcode');
                const postcode = postcodeInput.value.trim();
                const noticePostcode = document.getElementById('noticePostcode');
                // Only check if postcode has a value
                if (postcode.length > 0) {
                    try {
                        const res = await axios.get(`ticketsc.php?action=postcode_lookup&postcode=${encodeURIComponent(postcode)}&find=1&o=${encodeURIComponent(this.clientCode)}&p=LT`);
                        if (!res.data.success) {
                            postcodeValid = false;
                            valid = false;
                            // Show error
                            if (noticePostcode) {
                                noticePostcode.textContent = res.data.message || "Invalid postcode.";
                                noticePostcode.style.display = 'block';
                            } else {
                                // fallback: field-level error
                                postcodeInput.setCustomValidity(res.data.message || "Invalid postcode.");
                                postcodeInput.reportValidity();
                            }
                        } else {
                            // Clear error
                            if (noticePostcode) noticePostcode.style.display = 'none';
                            postcodeInput.setCustomValidity('');
                        }
                    } catch (e) {
                        postcodeValid = false;
                        valid = false;
                        if (noticePostcode) {
                            noticePostcode.textContent = "Could not verify postcode. Try again.";
                            noticePostcode.style.display = 'block';
                        } else {
                            postcodeInput.setCustomValidity("Could not verify postcode.");
                            postcodeInput.reportValidity();
                        }
                    }
                }
            }

            // build required inputs list:
            //  - always require [required]
            //  - if contact step & telephone has a value, also validate #telephone
            let requiredInputs = Array.from(current.querySelectorAll('[required]'));
            if (isContactStep && current.querySelector('#telephone').value.trim().length > 0) {
                requiredInputs.push(current.querySelector('#telephone'));
            }

            let touchedAll = true;
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    touchedAll = false;
                    input.reportValidity();
                    valid = false;
                } else if (input.id === 'mobile') {
                    const {
                        error
                    } = this.sanitizeAndValidateMobile(input, true);
                    if (error) {
                        document.querySelector('#noticeMobile').textContent = error;
                        input.reportValidity();
                        valid = false;
                    }
                } else if (input.id === 'telephone') {
                    const {
                        error
                    } = this.sanitizeAndValidateLandline(input, true);
                    if (error) {
                        document.querySelector('#noticeLandline').textContent = error;
                        input.reportValidity();
                        valid = false;
                    }
                } else if (!input.checkValidity()) {
                    input.reportValidity();
                    valid = false;
                }
            });

            // now handle the SMS‐verify block if present
            const verifyBlock = current.querySelector('#mobile_verify');
            if (verifyBlock) {
                const mobile = current.querySelector('#mobile');
                const sendBtn = document.getElementById('verify_button_mobile');
                const verify = verifyBlock;
                const notice = document.getElementById('noticeMobileVerify');
                const mval = mobile.value.trim();
                const ival = verify.value.trim();

                if (!mval && !sendBtn.disabled) {
                    valid = false;
                    notice.textContent = 'Please enter your mobile number and press “Send SMS.”';
                    notice.style.display = 'block';
                } else if (mval && !sendBtn.disabled) {
                    notice.textContent = 'Please press “Send SMS” to receive your code.';
                    notice.style.display = 'block';
                } else if (mval && sendBtn.disabled && !ival) {
                    notice.textContent = 'Please enter the 4-digit code we sent you.';
                    notice.style.display = 'block';
                } else if (mval && sendBtn.disabled) {
                    if (!/^\d{4}$/.test(ival)) {
                        valid = false;
                        notice.textContent = 'Enter the 4-digit code we sent you.';
                        notice.style.display = 'block';
                    } else {
                        notice.style.display = 'none';
                    }
                }
            }

            return valid && touchedAll;
        };

        // Button handlers for moving between steps
        document.querySelector("#nextBtn0")?.addEventListener("click", async () => {
            if (await validateCurrentStep()) this.showStep(1);
        });
        document.querySelector("#prevBtn1")?.addEventListener("click", () => this.showStep(0));
        document.querySelector("#nextBtn1")?.addEventListener("click", async () => {
            if (await validateCurrentStep()) this.showStep(2);
        });
        document.querySelector("#prevBtn2")?.addEventListener("click", () => this.showStep(1));
        document.querySelector("#nextBtn2")?.addEventListener("click", async () => {
            if (await validateCurrentStep()) this.showStep(3);
        });
        document.querySelector("#prevBtn3")?.addEventListener("click", () => this.showStep(2));
        document.querySelector("#nextBtn3")?.addEventListener("click", async () => {
            if (await validateCurrentStep()) this.showStep(4);
        });

        // Step indicators for direct navigation
        steps.forEach((step, idx) => {
            step.addEventListener('click', () => {
                if (idx <= this.currentStep || validateCurrentStep()) {
                    this.showStep(idx);
                }
            });
        });

        // Prevent 'Enter' from submitting early
        document.querySelectorAll('.step-content input, select, textarea').forEach(input => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const nextButton = document.querySelector(`#nextBtn${this.currentStep}`);
                    if (nextButton) nextButton.click();
                }
            });
        });

        // Initialize the first step
        this.showStep(0);
    },

    applyButtonStylesFromOptions(options) {
        // TODO if needed as we customise more clients and pass in more params 
        // const host = document;
        // if (options?.btnStyles) {
        //     host.style.setProperty('--widget-btn-bg', options.btnStyles.bg || '#FF4900');
        //     host.style.setProperty('--widget-btn-color', options.btnStyles.color || '#fff');
        //     host.style.setProperty('--widget-btn-border', options.btnStyles.border || '1px solid #FF4900');
        //     host.style.setProperty('--widget-btn-border-radius', options.btnStyles.borderRadius || '.25rem');
        //     host.style.setProperty('--widget-btn-padding', options.btnStyles.padding || '0.375rem 0.75rem');
        //     host.style.setProperty('--widget-btn-hover-bg', options.btnStyles.hoverBg || '#374151');
        // }
    },

    applyHostMarginsFromOptions(options) {
        if (options?.marginTop) {
            document.documentElement.style.setProperty('--widget-margin-top', options.marginTop);
        }
        if (options?.marginBottom) {
            document.documentElement.style.setProperty('--widget-margin-bottom', options.marginBottom);
        }
    },

    applyStyles() {
        const bodyStyles = window.getComputedStyle(document.body);
        const form = document.querySelector('#embedForm');
        if (form) {
            form.style.backgroundColor = bodyStyles.backgroundColor;
            form.style.color = bodyStyles.color;
            form.style.fontFamily = bodyStyles.fontFamily;
            form.style.padding = '20px';
            form.style.borderRadius = '8px';
            form.style.boxSizing = 'border-box';

            // Query all form elements EXCEPT radio or checkbox
            const formElements = form.querySelectorAll('input:not([type="radio"]):not([type="checkbox"]):not([type="hidden"]), select, textarea');
            formElements.forEach(element => {
                element.style.width = '100%';
                element.style.padding = '10px';
                element.style.margin = '10px 0';
                element.style.boxSizing = 'border-box';
            });

            this.applyButtonStyles();
        }
    },

    applyButtonStyles() {
        let clientButton;
        if (this.buttonId) {
            clientButton = document.getElementById(this.buttonId);
        } else {
            const allButtons = document.querySelectorAll('button');
            const widgetButton = document.querySelector('#submit-button');
            clientButton = [...allButtons].reverse().find((button) => button !== widgetButton);
        }
        if (clientButton) {
            const computedStyle = window.getComputedStyle(clientButton);
            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.style.backgroundColor = computedStyle.backgroundColor;
                submitButton.style.color = computedStyle.color;
                submitButton.style.borderRadius = computedStyle.borderRadius;
                submitButton.style.padding = computedStyle.padding;
                submitButton.style.border = computedStyle.border;
                submitButton.style.fontFamily = computedStyle.fontFamily;
                submitButton.style.fontSize = computedStyle.fontSize;
            }
        }
    },

    generateReferenceBoxesSVG() {
        let xPosition = 35;
        let referenceBoxes = '';
        for (let i = 0; i < 15; i++) {
            referenceBoxes += `<rect x="${xPosition + i * 40}" y="250" width="40" height="40" fill="none" stroke="black" />`;
        }
        return referenceBoxes;
    },

    setupFindAddress() {
    const findButton = document.querySelector("#findAddressBtn");
    if (!findButton) return;

    findButton.addEventListener("click", async () => {
        const postcodeInput = document.getElementById('postcode');
        const postcode = (postcodeInput?.value || '').trim();

        const notice       = document.getElementById('noticePostcode');
        const dropdown     = document.getElementById('addressDropdown');
        const selectionRow = document.getElementById('addressSelection');

        console.log('[FindAddress] Clicked. Postcode:', postcode);
        if (!postcode) {
        if (notice) notice.textContent = 'Please enter a postcode.';
        return;
        }
        if (!dropdown)     console.warn('[FindAddress] #addressDropdown not found');
        if (!selectionRow) console.warn('[FindAddress] #addressSelection not found');

        // Reset UI
        if (notice)       notice.textContent = 'Looking up...';
        if (dropdown)     dropdown.innerHTML = '';
        if (selectionRow) selectionRow.style.display = 'none';

        // Helper: clear current address fields
        const scope = selectionRow?.closest('.step-content') || document;
        const clearFields = () => {
        const q = (sel) => scope.querySelector(sel);
        ['#address_1','#address_2','#address_3','#town','#county'].forEach(sel => {
            const el = q(sel); if (el) el.value = '';
        });
        };

        try {
        const url = `ticketsc.php?action=postcode_lookup&postcode=${encodeURIComponent(postcode)}&find=0&o=${encodeURIComponent(this.clientCode)}&p=LT`;
        console.log('[FindAddress] GET:', url);

        const resp = await axios.get(url);
        console.log('[FindAddress] Raw response:', resp);

        const addresses = resp?.data?.data;
        console.log('[FindAddress] Parsed addresses:', addresses);

        if (!Array.isArray(addresses) || addresses.length === 0) {
            console.warn('[FindAddress] No addresses found for:', postcode);
            if (notice) notice.textContent = 'No addresses found.';
            if (dropdown) dropdown.innerHTML = '';
            if (selectionRow) selectionRow.style.display = 'none';
            clearFields(); // clear previous choice
            return;
        }

        // Build dropdown options
        dropdown.innerHTML = '';
        if (addresses.length > 1) {
            const ph = document.createElement('option');
            ph.value = '';
            ph.text  = 'Select address…';
            ph.disabled = true;
            ph.selected = true;
            dropdown.appendChild(ph);
        }

        const formatDisplay = (a) =>
            [a.address_line_1, a.address_line_2, a.address_line_3, a.town, a.county]
            .filter(Boolean)
            .join(' ');

        addresses.forEach((addr, i) => {
            const opt = document.createElement('option');
            opt.value = String(i);
            // Prefer backend display_address (no postcode); fallback to formatted lines
            opt.text  = addr.display_address || formatDisplay(addr) || addr.address || '';
            dropdown.appendChild(opt);
        });

        // Show selection UI
        if (selectionRow) selectionRow.style.display = 'block';
        if (notice) notice.textContent = '';

        // Fill helper (scoped to visible step; avoids cloned IDs)
        const fillFromIndex = (idx) => {
            console.log('[FindAddress] fillFromIndex idx:', idx);
            const a = addresses[idx];
            if (!a) return;

            // Optional diagnostics: duplicate IDs
            ['#address_1','#address_2','#address_3','#town','#county','#postcode'].forEach(sel => {
            const n = document.querySelectorAll(sel).length;
            if (n > 1) console.warn('[FindAddress] duplicate ID detected:', sel, 'count=', n);
            });

            const q = (sel) => scope.querySelector(sel);
            const f1 = q('#address_1');
            const f2 = q('#address_2');
            const f3 = q('#address_3');
            const ft = q('#town');
            const fc = q('#county');
            const fp = q('#postcode');

            if (f1) f1.value = a.address_line_1 || '';
            if (f2) f2.value = a.address_line_2 || '';
            if (f3) f3.value = a.address_line_3 || '';
            if (ft) ft.value = a.town || '';
            if (fc) fc.value = a.county || '';
            if (fp) fp.value = a.postcode || postcode; // keep uppercase from backend

            console.log('[FindAddress] after fill ->', {
            address_1: f1?.value, address_2: f2?.value, address_3: f3?.value,
            town: ft?.value, county: fc?.value, postcode: fp?.value
            });
        };

        // Change handler: only fill after a real choice
        dropdown.onchange = () => {
            const v = dropdown.value;
            if (v === '' || Number.isNaN(Number(v))) return; // ignore placeholder
            fillFromIndex(Number(v));
        };

        // Auto-fill ONLY when exactly one address
        if (addresses.length === 1) {
            dropdown.selectedIndex = 0; // only option
            fillFromIndex(0);
        } else {
            clearFields(); // force explicit choice on multi-result lookups
        }

        // Resize after UI change
        requestAnimationFrame(() => {
            const h = this.getHeight() + 30;
            console.log('[FindAddress] Requesting resize height:', h);
            window.parent.postMessage({ type: 'resize', height: h }, '*');
        });

        } catch (err) {
        console.error('[FindAddress] Error:', err);
        if (notice) {
            notice.innerHTML = '<p class="error-message">Error retrieving addresses. Please try again later.</p>';
        }
        clearFields(); // clear on error too
        }
    });
    },

    // setupPaymentToggle() {
    //     const payBtn = document.getElementById('payBtn');
    //     const paymentCard = document.getElementById('paymentCard');
    //     const postPayment = document.getElementById('postPayment');
    //     const form = document.getElementById('embedForm');

    //     if (!payBtn || !paymentCard || !postPayment || !form) return;

    //     payBtn.addEventListener('click', async (e) => {
    //         e.preventDefault(); // don’t accidentally do a native submit
    //         payBtn.disabled = true;
    //         payBtn.textContent = 'Processing…';

    //         // this will trigger your setupSubmit() listener
    //         if (typeof form.requestSubmit === 'function') {
    //             form.requestSubmit();
    //         } else {
    //             form.submit();
    //         }

    //         try {
    //             await new Promise(r => setTimeout(r, 800));

    //             // on “success” swap out the UI
    //             // paymentCard.style.display = 'none';
    //             // postPayment.style.display = 'block';

    //             requestAnimationFrame(() => {
    //                 window.parent.postMessage({
    //                     type: 'resize',
    //                     height: document.documentElement.scrollHeight + 30,
    //                     scroll: true
    //                 }, '*');
    //             });
    //         } catch (err) {
    //             console.error(err);
    //             payBtn.disabled = false;
    //             payBtn.textContent = 'Pay';
    //             alert('Payment failed, please try again.');
    //         }
    //     });
    // },

    // setupSubmit(logOnly = false) {
    //     const form = document.querySelector('#embedForm');
    //     form.addEventListener('submit', async (e) => {
    //         e.preventDefault();

    //         const submitButton = form.querySelector('button[type="submit"]');
    //         if (submitButton) {
    //             submitButton.disabled = true;
    //             submitButton.innerText = 'Submitting...';
    //         }

    //         // ----- Field Gathering -----
    //         const formData = new FormData(form);

    //         // Invert logic for BWH contact prefs
    //         // (If NOT checked, then "please keep me up to date" = 1)
    //         const pref_email = formData.has('pref_email');
    //         const pref_post = !formData.has('pref_post'); // Note the inversion!
    //         const pref_telephone = !formData.has('pref_telephone'); // Note the inversion!

    //         // Date of birth handling (convert DD-MM-YYYY to YYYY-MM-DD for backend)
    //         let rawDob = formData.get('dob') || '';
    //         let dobFormatted = rawDob;
    //         if (rawDob.match(/^\d{2}-\d{2}-\d{4}$/)) {
    //             const [dd, mm, yyyy] = rawDob.split('-');
    //             dobFormatted = `${yyyy}-${mm}-${dd}`;
    //         }

    //         // Build the payload
    //         const payload = {
    //             title: formData.get('title') || '',
    //             name_first: formData.get('name_first') || '',
    //             name_last: formData.get('name_last') || '',
    //             dob: dobFormatted,
    //             email: formData.get('email') || '',
    //             mobile: formData.get('mobile') || '',
    //             mobile_verify: formData.get('mobile_verify') || '',
    //             telephone: formData.get('telephone') || '',
    //             postcode: formData.get('postcode') || '',
    //             address_1: formData.get('address_1') || '',
    //             address_2: formData.get('address_2') || '',
    //             address_3: formData.get('address_3') || '',
    //             town: formData.get('town') || '',
    //             county: formData.get('county') || '',
    //             quantity: formData.get('quantity') || '1',
    //             draws: formData.get('draws') || '1',

    //             // Preferences
    //             pref_email: pref_email ? 1 : 0,
    //             pref_post: pref_post ? 1 : 0,
    //             pref_telephone: pref_telephone ? 1 : 0,

    //             // Consents
    //             gdpr: formData.has('gdpr') ? 1 : 0,
    //             terms: formData.has('terms') ? 1 : 0,
    //             age: formData.has('age') ? 1 : 0,

    //             collection_date: formData.get('collection_date') || '',
    //         };

    //         // Add other flags (in case they're ever needed)
    //         ['gdpr', 'signed', 'terms', 'age'].forEach(flag => {
    //             if (formData.get(flag)) {
    //                 payload[flag] = 'on';
    //             }
    //         });

    //         const urlEncodedData = new URLSearchParams(payload);

    //         if (logOnly) {
    //             this.showThankYouView('TEST_REF', 'TEST_EMAIL');
    //             return;
    //         }

    //         try {
    //             // Send as URL-encoded POST (matches PHP expectations)
    //             const response = await axios.post(
    //                 'ticketsc.php',
    //                 urlEncodedData
    //             );
    //             const responseData = response.data;

    //             console.log('data: ', responseData);
    //             if (responseData.success) {
    //                 console.log('Data for Pay is valid');
    //                 window.location.reload();
    //                 return;
    //             } else {
    //                 // Handle validation errors from PHP
    //                 if (Array.isArray(responseData.errors) && responseData.go) {
    //                     // Go to the relevant step
    //                     const stepMap = {
    //                         'about': 0,
    //                         'contact': 0,
    //                         'address': 0,
    //                         'requirements': 1,
    //                         'smallprint': 1
    //                     };
    //                     const stepIdx = stepMap[responseData.go] ?? 0;
    //                     this.selectStep(stepIdx);

    //                     // Show first error as a toast or field-level message
    //                     this.handleErrors(responseData);
    //                 } else {
    //                     alert(responseData.message || "Unknown error, please try again.");
    //                 }
    //             }
    //         } catch (error) {
    //             alert('There was an error submitting the form. Please try again later.');
    //         } finally {
    //             if (submitButton) {
    //                 submitButton.disabled = false;
    //                 submitButton.innerText = 'Pay';
    //             }
    //         }
    //     });
    // },

    setupPaymentToggle() {
        const payBtn = document.getElementById('payBtn');
        const paymentCard = document.getElementById('paymentCard');
        const postPayment = document.getElementById('postPayment');
        const form = document.getElementById('embedForm');

        if (!payBtn || !paymentCard || !postPayment || !form) return;

        payBtn.addEventListener('click', (e) => {
            // **NO e.preventDefault()!** Let the browser do its thing.
            payBtn.disabled = true;
            payBtn.textContent = 'Processing…';

            // Optionally: field cleanup or light JS validation here (not required)
            // You could do any pre-submit field formatting here if needed.

            // Submit the form the classic way
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }

            // UI changes after payment will happen on the **new page render**
            // so you don’t need to do anything else here!
        });
    },

    setupSubmit() {
        const form = document.getElementById('embedForm');
        if (!form) return;

        // Only attach if you want to preprocess fields (optional)
        form.addEventListener('submit', (e) => {
            // DO NOT call e.preventDefault();

            // Optionally, perform lightweight value cleanup here.
            // For example, format dates, strip spaces, etc.

            // That's it! Browser will submit POST and load PHP step 2.
        });
    },


    // Handle errors and display the first error, also navigate to the relevant step
    handleErrors(responseData) {
        // Clear previous error highlights
        const errorFields = document.querySelectorAll('.error');
        errorFields.forEach(field => {
            field.classList.remove('error');
            field.nextElementSibling?.classList.add('hidden');
        });

        // Store errors in this.errorsResponse for reference
        this.errorsResponse = [];

        // Define a lookup for error types and their associated step indexes
        const errorLookup = {
            'phone_number': 0,
            'landline_number': 0,
        };

        // If response data exists and contains an error message
        if (responseData && !responseData.success && responseData.message && responseData.errors) {

            // Array to hold all errors and their respective step indexes
            let errors = [];

            for (let currentError of responseData.errors) {
                // Check if the error is related to a phone number
                if (currentError.includes('Telephone number (mobile) is not verified')) {
                    errors.push({
                        message: currentError,
                        step: errorLookup.phone_number
                    });
                    this.showErrorOnField('mobile_verify', currentError); // Show error on the mobile code verify field


                } else if (currentError.includes('is not a valid phone number')) {
                    errors.push({
                        message: currentError,
                        step: errorLookup.landline_number
                    });
                    this.showErrorOnField('telephone', currentError); // Show error on the landline field

                } else {
                    // Other general errors
                    this.errorsResponse.push({
                        message: currentError
                    });
                }
            }

            // Store the errors in this.errorsResponse
            this.errorsResponse = [...this.errorsResponse, ...errors];

            // Go to the earliest step with an error
            if (errors.length > 0) {
                const earliestError = Math.min(...errors.map(error => error.step));
                this.selectStep(earliestError); // Go to the first step with an error
            }
        }
    },

    showThankYouView(reference = null, email = null) {
        const stepContents = document.querySelectorAll('.step-content');
        stepContents.forEach(content => content.classList.remove('active'));

        const thankYouStep = document.querySelector('.step-content[data-step-content="5"]');
        if (thankYouStep) {
            thankYouStep.classList.add('active');

            if (reference) {
                const refContainer = thankYouStep.querySelector('.reference-number');
                if (refContainer) {
                    refContainer.innerHTML = reference;
                }
            }
            // Display email address if available
            if (email) {
                const emailContainer = thankYouStep.querySelector('.email-address');
                if (emailContainer) {
                    emailContainer.innerHTML = email;
                }
            }
        }

        // 1) Hide the entire stepper bar
        const stepper = document.getElementById('stepper');
        if (stepper) {
            stepper.style.display = 'none';
        }

        // 2) Hide all other panels except the Thank You one
        document.querySelectorAll('.step-content').forEach(panel => {
            if (panel.dataset.stepContent !== '5') {
                panel.style.display = 'none';
            }
        });

        // 3) Always ensure the Thank You panel is visible
        const thankYou = document.querySelector('.step-content[data-step-content="5"]');
        if (thankYou) {
            thankYou.style.display = 'block';
            thankYou.classList.add('active');
        }

        // Resize after stepper removal
        setTimeout(() => {
            window.parent.postMessage({
                type: 'resize',
                height: this.getHeight() + 30,
                scroll: true,
            }, '*');
        }, 300);
    },

    // Example method to display the error on the specified field
    showErrorOnField(fieldName, errorMessage) {
        // Special case for mobile verification code field (uses its own error div)
        if (fieldName === 'mobile_verify') {
            const notice = document.getElementById('noticeMobileVerify');
            if (notice) {
                notice.textContent = errorMessage;
                notice.style.display = 'block';
            }
            // Also add error class to the input if you want
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) field.classList.add('error');
            // Optionally scroll to the field
            if (field) field.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            return; // Done!
        }

        // Normal field error fallback
        const field = document.querySelector(`[name="${fieldName}"]`);
        const errorElement = document.createElement('span');
        errorElement.classList.add('error-message');
        errorElement.textContent = errorMessage;

        // Clear any existing error message in the parent
        const existingError = field?.parentNode.querySelector('.error-message');
        if (existingError) existingError.remove();

        // Add new error message
        field?.parentNode.appendChild(errorElement);

        // Add an error class to the field
        field?.classList.add('error');

        // Scroll to the first error field
        field?.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    },

    // Function to handle general error messages
    showGenericError(errorMessage) {
        alert(errorMessage); // Or display it in a more appropriate UI element
    },
    // Function to select a specific step (1-based index)
    selectStep(stepIndex) {
        const steps = Array.from(document.querySelectorAll('.step'));
        const stepContents = Array.from(document.querySelectorAll('.step-content'));

        // Only highlight circles for the first 5 steps
        steps.forEach((step, idx) => {
            step.classList.toggle('active', idx === stepIndex && stepIndex < steps.length);
        });

        stepContents.forEach((content, idx) => {
            content.classList.toggle('active', idx === stepIndex);
        });

        let allowScroll = this.currentStep > stepIndex || stepIndex > 0;

        this.currentStep = stepIndex;

        // Inject self-exclusion email only when step 4 becomes visible
        if (stepIndex === 4) {
            waitForElement('#selfExclusionPara', (selfExclusion) => {
                if (selfExclusion && selfExclusion.innerHTML.trim() === '') {
                    selfExclusion.innerHTML =
                        'To self-exclude, please email: <a href="mailto:' +
                        this.selfExclusionEmail +
                        '">' + this.selfExclusionEmail + '</a>.';
                }
            });

            const gdprContainer = document.querySelector('#gdprStatementContainer');
            if (gdprContainer) {
                gdprContainer.innerHTML =
                    '<p>' +
                    "Your support makes our work possible. We'd love to keep in touch with you to tell you more " +
                    'about our work and how you can support it. ' +
                    "We'll do this by the options you chose above and you can change these preferences at any " +
                    'time by calling or e-mailing us on ' +
                    '<strong>' + this.contactPhone + '</strong> or <a href="mailto:' + this.contactEmail + '">' + this.contactEmail + '</a>.' +
                    '</p>' +
                    '<p>We will never sell your details on to anyone else.</p>';
            }
        }

        // Scroll to the selected step content
        requestAnimationFrame(() => {
            if (allowScroll === true) {
                window.parent.postMessage({
                    type: 'resize',
                    height: stepIndex === 0 ? 600 : this.getHeight() + 30,
                    scroll: true,
                    step: stepIndex,
                }, '*');
            } else {
                window.parent.postMessage({
                    type: 'resize',
                    height: stepIndex === 0 ? 600 : this.getHeight() + 30,
                }, '*');
            }
        });
    },

    /**
     * Sanitize and validate the mobile input on each keystroke.
     * This function:
     *  - Removes any unwanted characters.
     *  - Ensures '+' is only at the beginning.
     *  - Converts '+44' to '0' when appropriate.
     *  - Sets an error message if the input doesn't meet UK mobile rules.
     */
    sanitizeAndValidateMobile(input, errorOnEmpty = false) {
        let value = input.value.trim();
        let error = '';

        if (errorOnEmpty === true && value?.length === 0) {
            return {
                value: '',
                error: 'Mobile is required.'
            };
        }

        // Allow only digits and '+'
        // value = value.replace(/[^+\d]/g, '');
        // Normalize: remove all non-digits except leading +
        value = value.replace(/(?!^\+)[^\d]/g, '');

        // If the value starts with '+', ensure it's either '+4' then '+44'
        if (value.startsWith('+')) {
            if (value.length === 2 && value !== '+4') {
                error = 'Non-UK mobile numbers are not allowed.';
            } else if (value.length === 3 && value !== '+44') {
                error = 'Non-UK mobile numbers are not allowed.';
            }
        }

        // Convert '+44' to domestic '0' when more than 3 characters are typed
        if (value.startsWith('+44') && value.length > 3) {
            value = '0' + value.slice(3);
            error = '';
        }

        if (value.startsWith('00')) {
            value = '0' + value.slice(2);
        }

        // For any other '+' combinations beyond 3 characters, show an error
        if (value.startsWith('+') && !value.startsWith('+44') && value.length > 3) {
            error = 'Non-UK mobile numbers are not allowed.';
        }

        // If it's exactly 10 digits and starts with 7 (likely mobile), prepend 0
        if (value.length === 10 && value.startsWith('7')) {
            value = '0' + value;
        }

        // Check domestic number: valid UK mobiles should start with '07'
        if (value.length > 0 && value.startsWith('0') && !value.startsWith('07')) {
            error = 'Please enter a valid UK mobile number starting with 07 or +447.';
        }

        // Validate that the number now starts with '07' and is exactly 11 digits.
        if (error.length === 0 && value.length > 0) {
            if (!value.startsWith('07')) {
                error = 'Please enter a UK mobile number starting with 07 or +447.';
            }
            // Trim if over 11 digits
            if (value.length > 11) {
                value = value.slice(0, 11);
            } else if (value.length < 11) {
                error = 'UK mobile numbers must be exactly 11 digits.';
            }
        }

        // Update the input element with the sanitized value.
        input.value = value;
        return {
            value,
            error
        };
    },

    /**
     * Sanitize and validate the landline input on each keystroke.
     * This function:
     *  - Removes any unwanted characters.
     *  - Ensures that the number follows the correct UK landline format.
     *  - Sets an error message if the input doesn't meet UK landline rules.
     *  - Allows empty values as valid.
     */
    sanitizeAndValidateLandline(input, errorOnEmpty = false) {
        let value = input.value.trim();
        let error = '';

        // If the input is empty, allow it as valid (if it's not required)
        if (value.length === 0) {
            return {
                value: '',
                error: ''
            };
        }

        // If not empty, apply the validation rules
        if (errorOnEmpty === true && value?.length === 0) {
            return {
                value: '',
                error: 'Landline number is required.'
            };
        }

        // Allow only digits, spaces, and plus sign
        value = value.replace(/[^+\d\s]/g, '');

        // Remove spaces for consistency
        value = value.replace(/\s+/g, '');

        // Ensure the number starts with valid UK prefixes like 01, 02, 03, 07, etc.
        const validPrefixes = ['01', '02', '03'];
        const startsWithValidPrefix = validPrefixes.some(prefix => value.startsWith(prefix));

        if (!startsWithValidPrefix) {
            error = 'Please enter a valid UK landline number starting with 01, 02 or 03.';
        }

        // Check if the length of the number is at least 10 digits
        if (value.length < 10) {
            error = 'Landline numbers must be at least 10 digits long.';
        }

        // Check the length of the number (should be no more than 12 digits)
        if (value.length > 12) {
            error = 'Landline numbers must be shorter.';
        }

        // Update the input element with the sanitized value
        input.value = value;

        return {
            value,
            error
        };
    },

    _applyRadioLayout(container) {
        const apply = () => {
            const w = container.getBoundingClientRect().width || 0;
            const mobile = w <= 600; // breakpoint by container, not window

            // quantity items
            container.querySelectorAll('[data-role="qty-item"], [data-role="week-item"]').forEach(el => {
            if (mobile) {
                el.style.flex = '1 1 calc(50% - 0.5rem)'; // 2-up
                el.style.minWidth = '0';
            } else {
                el.style.flex = '0 0 auto';
                el.style.minWidth = '';
            }
            });
        };

        // run now + on resize/orientation/content changes
        apply();

        // stash one observer per container
        if (!container._ro) {
            const ro = new ResizeObserver(() => apply());
            ro.observe(container);
            container._ro = ro;
        }
    },

    renderTicketStep(reverse = false) {
        const {
            ticketPrice: pricePerDraw,
            maxPurchase,
            quantities,
            weekOptions,
            nextDrawDate,
        } = this.options;

        // Scope for DOM writes (avoid duplicate IDs across cloned panels)
        const scope = document.querySelector('.step-content.active') || document;

        // Save previous selections (prefer active step, fallback to whole form)
        const form = document.getElementById('embedForm') || document;

        const prevQty =
            (scope.querySelector('input[name="quantity"]:checked') ||
                form.querySelector('input[name="quantity"]:checked'))?.value ?? null;

        const prevDraws =
            (scope.querySelector('input[name="draws"]:checked') ||
                scope.querySelector('input[name="draws"]') ||
                form.querySelector('input[name="draws"]:checked') ||
                form.querySelector('input[name="draws"]'))?.value ?? null;

        // Containers (these IDs exist in the ticket panel that includes the radios)
        const qtyContainer = scope.querySelector('#quantityRadios');
        const weekCard = scope.querySelector('#week-card');
        const weekContainer = scope.querySelector('#weekRadios');


        if (!qtyContainer || !weekContainer) return;

        function toggleScrollableIfNeeded(container) {
            if (!container) return;
            container.classList.remove('is-scrollable');
            requestAnimationFrame(() => {
                const needScroll = container.scrollWidth > container.clientWidth + 1;
                if (needScroll) container.classList.add('is-scrollable');
            });
        }

        function wireRecalcOnResize(containers = []) {
            let t;
            window.addEventListener('resize', () => {
                clearTimeout(t);
                t = setTimeout(() => containers.forEach(toggleScrollableIfNeeded), 100);
            });
        }

        // Static labels
        const nd = scope.querySelector('#next-draw-date');
        const ppd = scope.querySelector('#price-per-draw');
        const mp = scope.querySelector('#max-purchase');

        if (nd) nd.textContent = nextDrawDate;
        if (ppd) ppd.textContent = parseFloat(pricePerDraw).toFixed(2);
        if (mp) mp.textContent = maxPurchase;

        const displayQuantities = reverse ? [...quantities].reverse() : quantities;

        // Build QUANTITY radios (single wrapping row)
        qtyContainer.innerHTML = '';
        const qtyRow = document.createElement('div');
        qtyRow.className = 'radios-row';
        qtyContainer.appendChild(qtyRow);

        displayQuantities.forEach((qty, i) => {
            const id = `quantity-${qty}`;
            const div = document.createElement('div');
            div.className = 'form-check';
            div.setAttribute('data-role', 'qty-item');
            div.innerHTML = `
      <input class="form-check-input qty-radio" type="radio" name="quantity" id="${id}"
             value="${qty}" ${i === 0 ? 'checked' : ''}>
      <label class="form-check-label" for="${id}">
        ${qty} ${qty === 1 ? 'ticket' : 'tickets'}
      </label>
    `;
            qtyRow.appendChild(div);
        });

        // Build WEEK radios (or hidden input if only one option)
        weekContainer.innerHTML = '';
        const weekEntries = Object.entries(weekOptions);

        if (weekEntries.length === 1) {
            if (weekCard) weekCard.style.display = 'none';
            const [draws] = weekEntries[0];
            weekContainer.innerHTML = `<input type="hidden" name="draws" value="${draws}">`;
        } else {
            if (weekCard) weekCard.style.display = '';
            const weekRow = document.createElement('div');
            weekRow.className = 'radios-row';
            weekContainer.appendChild(weekRow);

            weekEntries.forEach(([draws, label], i) => {
                const id = `draws-${draws}`;
                const div = document.createElement('div');
                div.className = 'form-check';
                div.setAttribute('data-role', 'week-item');
                div.innerHTML = `
        <input class="form-check-input week-radio" type="radio" name="draws" id="${id}"
               value="${draws}" ${i === 0 ? 'checked' : ''}>
        <label class="form-check-label" for="${id}">${label}</label>
      `;
                weekRow.appendChild(div);
            });
        }

        // Restore previous selections (if any)
        if (prevQty) {
            const el =
                scope.querySelector(`input[name="quantity"][value="${CSS.escape(prevQty)}"]`) ||
                form.querySelector(`input[name="quantity"][value="${CSS.escape(prevQty)}"]`);
            if (el) el.checked = true;
        }

        if (prevDraws) {
            const drawsEl =
                scope.querySelector(`input[name="draws"][value="${CSS.escape(prevDraws)}"]`) ||
                scope.querySelector('input[name="draws"]') ||
                form.querySelector(`input[name="draws"][value="${CSS.escape(prevDraws)}"]`) ||
                form.querySelector('input[name="draws"]');
            if (drawsEl) {
                if (drawsEl.type === 'radio') drawsEl.checked = true;
                else drawsEl.value = prevDraws;
            }
        }

        toggleScrollableIfNeeded(qtyContainer);
        toggleScrollableIfNeeded(weekContainer);

        if (!this._scrollRecalcBound) {
            wireRecalcOnResize([qtyContainer, weekContainer]);
            this._scrollRecalcBound = true;
        }

        // Total elements (may appear in multiple panels; update all that exist)
        // Total elements (may appear in multiple panels; update all that exist)
        const costEls = form.querySelectorAll('#total-cost, #total-cost2');

        // Ensure numeric
        const price = parseFloat(pricePerDraw) || 0;

        function updateTotalCost() {
            const q = getSelectedQty();
            const d = getSelectedDraws();
            const total = q * price * d;
            costEls.forEach((el) => (el.textContent = total.toFixed(2)));
        }

        // Always compute selection from the whole form (so “Complete” step still shows correct total)
        function getSelectedQty() {
            return +(
                (scope.querySelector('input[name="quantity"]:checked') ||
                    form.querySelector('input[name="quantity"]:checked'))?.value || 0
            );
        }

        function getSelectedDraws() {
            const drawsEl =
                scope.querySelector('input[name="draws"]:checked') ||
                scope.querySelector('input[name="draws"]') ||
                form.querySelector('input[name="draws"]:checked') ||
                form.querySelector('input[name="draws"]');
            return +(drawsEl?.value || 0);
        }

        function updateTotalCost() {
            const q = getSelectedQty();
            const d = getSelectedDraws();
            const total = q * pricePerDraw * d;
            costEls.forEach((el) => (el.textContent = total.toFixed(2)));
        }

        // Bind to current rendered radios (these live in the ticket step)
        const qtyRadios = scope.querySelectorAll('.qty-radio');
        const weekRadios = scope.querySelectorAll('.week-radio');

        function enforceMax() {
            const selQty = getSelectedQty();
            const selDraws = getSelectedDraws();

            // Ensure numeric price for comparisons
            const price = parseFloat(pricePerDraw) || 0;

            // Disable qty radios that would exceed max (given current draws)
            qtyRadios.forEach((input) => {
                const q = +input.value;
                input.disabled = (q * selDraws * price) > maxPurchase;
                if (input.checked && input.disabled) input.checked = false;
            });

            if (weekRadios.length > 0) {
                // Disable week radios that would exceed max (given current qty)
                weekRadios.forEach((input) => {
                    const d = +input.value;
                    input.disabled = (selQty * d * price) > maxPurchase;
                    if (input.checked && input.disabled) input.checked = false;
                });

                // If nothing checked (because we disabled the chosen one), pick first enabled
                if (![...qtyRadios].some((i) => i.checked)) {
                    const firstEnabledQty = [...qtyRadios].find((i) => !i.disabled);
                    if (firstEnabledQty) firstEnabledQty.checked = true;
                }

                if (![...weekRadios].some((i) => i.checked)) {
                    const firstEnabledWeek = [...weekRadios].find((i) => !i.disabled);
                    if (firstEnabledWeek) firstEnabledWeek.checked = true;
                }
            } else {
                // Hidden draws input case: just ensure we have some qty selected
                if (![...qtyRadios].some((i) => i.checked)) {
                    const firstEnabledQty = [...qtyRadios].find((i) => !i.disabled);
                    if (firstEnabledQty) firstEnabledQty.checked = true;
                }
            }
        }

        [...qtyRadios, ...weekRadios].forEach((el) => {
            el.addEventListener('change', () => {
                enforceMax();
                updateTotalCost();
            });
        });

        enforceMax();
        updateTotalCost();

        // Resize after render
        requestAnimationFrame(() => {
            window.parent.postMessage(
                { type: 'resize', height: this.getHeight() + 30 },
                '*'
            );
        });
    },

};

// Helper function to wait for an element.
function waitForElement(selector, callback) {
    const el = document.querySelector(selector);
    if (el) {
        callback(el);
        return;
    }
    const observer = new MutationObserver((mutations, obs) => {
        const el = document.querySelector(selector);
        if (el) {
            obs.disconnect();
            callback(el);
        }
    });
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

async function loadWidgetHtml(containerId, url) {
    const container = document.getElementById(containerId);
    if (!container) throw new Error('No container found');

    const resp = await fetch(url);
    const html = await resp.text();
    container.innerHTML = html;
}


document.addEventListener('DOMContentLoaded', async function () {
    await loadWidgetHtml('ticket-widget-app', './ticketsc/ticketsc.html');

    // Clone the config deeply (safe for objects/arrays)
    const config = JSON.parse(JSON.stringify(window.TICKET_WIDGET_CONFIG));
    // Remove the global reference
    delete window.TICKET_WIDGET_CONFIG;

    // set the hidden field(s) using config
    if (config) {
        const nextDrawDateRaw = config.nextDrawDateRaw;
        const collectionDateField = document.getElementById('collection_date');
        if (collectionDateField && nextDrawDateRaw) {
            collectionDateField.value = nextDrawDateRaw;
        }
    }
    setTimeout(() => {

        this.smsSent = false;
        this.lastSentMobile = '';

        // wire up debug buttons
        document.querySelectorAll('#debug-toolbar button').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.getAttribute('data-debug-step');
                const cfg = wizardLayouts[key];
                if (cfg) {
                    TicketWidget.buildWizard(cfg);
                }
            });
        });

        const wizardLayouts = {
            oneStep: {
                count: 1,
                steps: [{
                    panels: [0, 1, 2, 3, 4],
                    title: 'All In One',
                    subtitle: 'Everything on one page'
                }]
            },

            twoStep: {
                count: 2,
                steps: [{
                    panels: [0, 1],
                    title: 'About & Contact',
                    subtitle: 'Personal Details & How We Reach You'
                },
                {
                    panels: [2, 3, 4],
                    title: 'Play & Complete',
                    subtitle: 'Ticket Info, GDPR & Payment'
                },
                ]
            },

            threeStep: {
                count: 3,
                steps: [{
                    panels: [0, 1],
                    title: 'About You',
                    subtitle: 'Personal Details & How We Reach You'
                },
                {
                    panels: [2, 3],
                    title: 'Ticket',
                    subtitle: 'Options, Ticket Info & GDPR'
                },
                {
                    panels: [4],
                    title: 'Complete',
                    subtitle: 'Confirmation & Payment'
                },
                ]
            },

            fourStep: {
                count: 4,
                steps: [{
                    panels: [0, 1],
                    title: 'About & Contact',
                    subtitle: 'Personal Details & How We Reach You'
                },
                {
                    panels: [2],
                    title: 'Ticket & Requirements',
                    subtitle: 'Options & Ticket Info'
                },
                {
                    panels: [3],
                    title: 'Prefs & GDPR',
                    subtitle: 'Preferences & GDPR'
                },
                {
                    panels: [4],
                    title: 'Complete',
                    subtitle: 'Confirmation & Payment'
                },
                ]
            },

            fiveStep: {
                count: 5,
                steps: [{
                    panels: [0],
                    title: 'About You',
                    subtitle: 'Personal Details'
                },
                {
                    panels: [1],
                    title: 'Contact',
                    subtitle: 'How We Reach You'
                },
                {
                    panels: [2],
                    title: 'Ticket & Requirements',
                    subtitle: 'Options & Ticket Info'
                },
                {
                    panels: [3],
                    title: 'Prefs & GDPR',
                    subtitle: 'Preferences & GDPR'
                },
                {
                    panels: [4],
                    title: 'Complete',
                    subtitle: 'Confirmation & Payment'
                },
                ]
            },
        };

        const layoutLookup = {
            'dhb': 'oneStep',
        }

        const clientCode = config.clientCode;
        const layoutKey = layoutLookup[clientCode] || 'oneStep'; // 'oneStep' | 'twoStep' | 'threeStep' | 'fourStep' | 'fiveStep'
        const customStepsConfig = wizardLayouts[layoutKey];

        config.customSteps = customStepsConfig;

        // Init the widget with the config
        TicketWidget.init(config);

    });
});
