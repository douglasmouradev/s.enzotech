/**
 * Enzo Tech — JavaScript principal
 * Máscaras, wizard, cálculos e AJAX
 */

(function () {
    'use strict';

    const baseUrl = window.ENZO_BASE_URL || '';

    // -------------------------------------------------------------------------
    // Utilitários
    // -------------------------------------------------------------------------

    function onlyDigits(value) {
        return (value || '').replace(/\D/g, '');
    }

    function formatMoedaInput(value) {
        let digits = onlyDigits(value);
        if (!digits) return '';
        digits = digits.replace(/^0+/, '') || '0';
        const num = parseInt(digits, 10) / 100;
        return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function parseMoeda(value) {
        if (!value) return 0;
        const cleaned = value.replace(/\./g, '').replace(',', '.');
        return parseFloat(cleaned) || 0;
    }

    function formatMoedaDisplay(value) {
        return 'R$ ' + (value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // -------------------------------------------------------------------------
    // Máscaras monetárias
    // -------------------------------------------------------------------------

    function initMoedaMasks() {
        document.querySelectorAll('[data-mask="moeda"]').forEach(function (input) {
            input.addEventListener('input', function () {
                const pos = input.selectionStart;
                const oldLen = input.value.length;
                input.value = formatMoedaInput(input.value);
                const newLen = input.value.length;
                input.setSelectionRange(pos + (newLen - oldLen), pos + (newLen - oldLen));
                input.dispatchEvent(new Event('moeda-change', { bubbles: true }));
            });
        });
    }

    // -------------------------------------------------------------------------
    // Máscara CPF
    // -------------------------------------------------------------------------

    function maskCpf(value) {
        const d = onlyDigits(value).slice(0, 11);
        return d
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    function initCpfMask() {
        document.querySelectorAll('[data-mask="cpf"]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = maskCpf(input.value);
            });
        });
    }

    // -------------------------------------------------------------------------
    // Máscara telefone
    // -------------------------------------------------------------------------

    function maskTelefone(value) {
        const d = onlyDigits(value).slice(0, 11);
        if (d.length <= 10) {
            return d.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3').trim();
        }
        return d.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3').trim();
    }

    function initTelefoneMask() {
        document.querySelectorAll('[data-mask="telefone"]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = maskTelefone(input.value);
            });
        });
    }

    // -------------------------------------------------------------------------
    // Máscara CEP
    // -------------------------------------------------------------------------

    function initCepMask() {
        document.querySelectorAll('[data-mask="cep"]').forEach(function (input) {
            input.addEventListener('input', function () {
                const d = onlyDigits(input.value).slice(0, 8);
                input.value = d.replace(/(\d{5})(\d{0,3})/, '$1-$2');
            });
        });
    }

    // -------------------------------------------------------------------------
    // Validação IMEI (15 dígitos)
    // -------------------------------------------------------------------------

    function validarImeiLuhn(digits) {
        if (digits.length !== 15) return false;
        let soma = 0;
        for (let i = 0; i < 14; i++) {
            let dig = parseInt(digits[i], 10);
            if (i % 2 === 1) { dig *= 2; if (dig > 9) dig -= 9; }
            soma += dig;
        }
        return ((10 - (soma % 10)) % 10) === parseInt(digits[14], 10);
    }

    function validarImei(input) {
        const digits = onlyDigits(input.value);
        const errorEl = input.parentElement.querySelector('.form-error') ||
            (function () {
                const el = document.createElement('div');
                el.className = 'form-error';
                input.parentElement.appendChild(el);
                return el;
            })();

        if (digits.length === 0) {
            errorEl.textContent = '';
            input.setCustomValidity('');
            return true;
        }

        if (!validarImeiLuhn(digits)) {
            errorEl.textContent = 'IMEI inválido (15 dígitos, verificação Luhn).';
            input.setCustomValidity('IMEI inválido');
            return false;
        }

        errorEl.textContent = '';
        input.setCustomValidity('');
        return true;
    }

    function initImeiValidation() {
        document.querySelectorAll('[data-validate="imei"]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = onlyDigits(input.value).slice(0, 15);
                validarImei(input);
            });
            input.addEventListener('blur', function () {
                validarImei(input);
            });
        });
    }

    // -------------------------------------------------------------------------
    // Cálculo de margem em tempo real
    // -------------------------------------------------------------------------

    function getMargemBadgeClass(margem) {
        if (margem >= 20) return 'badge-green';
        if (margem >= 10) return 'badge-amber';
        return 'badge-red';
    }

    function atualizarPainelMargem() {
        const compraInput = document.getElementById('valor_compra');
        const vendaInput = document.getElementById('valor_venda');
        const panel = document.getElementById('margin-panel');

        if (!compraInput || !vendaInput || !panel) return;

        const compra = parseMoeda(compraInput.value);
        const venda = parseMoeda(vendaInput.value);
        const lucro = venda - compra;
        const margem = compra > 0 ? ((venda - compra) / compra) * 100 : 0;

        const lucroEl = panel.querySelector('[data-mp="lucro"]');
        const margemEl = panel.querySelector('[data-mp="margem"]');
        const badgeEl = panel.querySelector('[data-mp="badge"]');

        if (lucroEl) lucroEl.textContent = formatMoedaDisplay(lucro);
        if (margemEl) margemEl.textContent = margem.toFixed(1).replace('.', ',') + '%';
        if (badgeEl) {
            badgeEl.className = 'badge ' + getMargemBadgeClass(margem);
            badgeEl.textContent = margem >= 20 ? 'Ótima' : margem >= 10 ? 'Boa' : 'Baixa';
        }
    }

    function initMargemCalculo() {
        const compraInput = document.getElementById('valor_compra');
        const vendaInput = document.getElementById('valor_venda');

        if (!compraInput || !vendaInput) return;

        [compraInput, vendaInput].forEach(function (input) {
            input.addEventListener('input', atualizarPainelMargem);
            input.addEventListener('moeda-change', atualizarPainelMargem);
        });

        atualizarPainelMargem();
    }

    // -------------------------------------------------------------------------
    // Forma de pagamento — campo parcelas
    // -------------------------------------------------------------------------

    function initFormaPagamento() {
        const select = document.getElementById('forma_pagamento');
        const parcelasGroup = document.getElementById('parcelas-group');

        if (!select || !parcelasGroup) return;

        function toggleParcelas() {
            const show = select.value === 'parcelado' || select.value === 'cartao_credito';
            parcelasGroup.style.display = show ? 'block' : 'none';
            const input = parcelasGroup.querySelector('input');
            if (input) input.required = select.value === 'parcelado';
        }

        select.addEventListener('change', toggleParcelas);
        toggleParcelas();
    }

    // -------------------------------------------------------------------------
    // Wizard de cadastro de venda (4 etapas)
    // -------------------------------------------------------------------------

    function initWizard() {
        const wizard = document.getElementById('venda-wizard');
        if (!wizard) return;

        let currentStep = 1;
        const totalSteps = 4;
        const panels = wizard.querySelectorAll('.wizard-panel');
        const steps = wizard.querySelectorAll('.wizard-step');
        const btnPrev = document.getElementById('wizard-prev');
        const btnNext = document.getElementById('wizard-next');
        const btnSubmit = document.getElementById('wizard-submit');

        function showStep(step) {
            currentStep = step;
            panels.forEach(function (panel) {
                panel.classList.toggle('active', parseInt(panel.dataset.step, 10) === step);
            });
            steps.forEach(function (s) {
                const num = parseInt(s.dataset.step, 10);
                s.classList.remove('active', 'done');
                if (num === step) s.classList.add('active');
                else if (num < step) s.classList.add('done');
            });

            if (btnPrev) btnPrev.style.display = step > 1 ? 'inline-flex' : 'none';
            if (btnNext) btnNext.style.display = step < totalSteps ? 'inline-flex' : 'none';
            if (btnSubmit) btnSubmit.style.display = step === totalSteps ? 'inline-flex' : 'none';
        }

        function validateStep(step) {
            const panel = wizard.querySelector('.wizard-panel[data-step="' + step + '"]');
            if (!panel) return true;

            const required = panel.querySelectorAll('[required]');
            for (let i = 0; i < required.length; i++) {
                if (!required[i].checkValidity()) {
                    required[i].reportValidity();
                    return false;
                }
            }

            if (step === 1) {
                const celularId = document.getElementById('celular_id');
                if (celularId && !celularId.value) {
                    alert('Selecione um celular disponível.');
                    return false;
                }
            }

            if (step === 3) {
                const cpf = document.getElementById('cpf');
                if (cpf && onlyDigits(cpf.value).length !== 11) {
                    alert('Informe um CPF válido com 11 dígitos.');
                    return false;
                }
                const consent = document.getElementById('consentimento_lgpd');
                if (consent && !consent.checked) {
                    alert('É necessário o consentimento do titular (LGPD).');
                    return false;
                }
            }

            return true;
        }

        if (btnPrev) {
            btnPrev.addEventListener('click', function () {
                if (currentStep > 1) showStep(currentStep - 1);
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', function () {
                if (validateStep(currentStep) && currentStep < totalSteps) {
                    showStep(currentStep + 1);
                }
            });
        }

        showStep(1);
    }

    // -------------------------------------------------------------------------
    // Select com busca de celular
    // -------------------------------------------------------------------------

    function initCelularSearch() {
        const wrap = document.getElementById('celular-search-wrap');
        if (!wrap) return;

        const searchInput = document.getElementById('celular_search');
        const hiddenInput = document.getElementById('celular_id');
        const list = document.getElementById('celular-search-list');
        const resumo = document.getElementById('celular-resumo');
        const options = wrap.querySelectorAll('.search-select-option');

        if (!searchInput || !hiddenInput || !list) return;

        function filterOptions(term) {
            const t = term.toLowerCase();
            let visible = 0;
            options.forEach(function (opt) {
                const text = opt.textContent.toLowerCase();
                const show = !t || text.includes(t);
                opt.style.display = show ? 'block' : 'none';
                if (show) visible++;
            });
            list.classList.toggle('open', visible > 0 && term.length > 0);
        }

        function selectCelular(opt) {
            hiddenInput.value = opt.dataset.id;
            searchInput.value = opt.dataset.label;
            list.classList.remove('open');
            options.forEach(function (o) {
                o.classList.toggle('selected', o === opt);
            });

            if (resumo) {
                resumo.classList.add('visible');
                resumo.querySelector('[data-r="marca"]').textContent = opt.dataset.marca;
                resumo.querySelector('[data-r="modelo"]').textContent = opt.dataset.modelo;
                resumo.querySelector('[data-r="imei"]').textContent = opt.dataset.imei;
                resumo.querySelector('[data-r="cor"]').textContent = opt.dataset.cor || '—';
                resumo.querySelector('[data-r="capacidade"]').textContent = opt.dataset.capacidade || '—';
                resumo.querySelector('[data-r="condicao"]').textContent = opt.dataset.condicao;
            }

            const vc = document.getElementById('valor_compra');
            const dc = document.getElementById('data_compra');
            if (vc && opt.dataset.valorCompra) { vc.value = opt.dataset.valorCompra; vc.dispatchEvent(new Event('input')); }
            if (dc && opt.dataset.dataCompra) dc.value = opt.dataset.dataCompra;
        }

        searchInput.addEventListener('focus', function () {
            filterOptions(searchInput.value);
            list.classList.add('open');
        });

        searchInput.addEventListener('input', function () {
            hiddenInput.value = '';
            if (resumo) resumo.classList.remove('visible');
            filterOptions(searchInput.value);
        });

        options.forEach(function (opt) {
            opt.addEventListener('click', function () {
                selectCelular(opt);
            });
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) {
                list.classList.remove('open');
            }
        });
    }

    // -------------------------------------------------------------------------
    // AJAX — busca comprador por CPF
    // -------------------------------------------------------------------------

    function initBuscaComprador() {
        const cpfInput = document.getElementById('cpf');
        if (!cpfInput) return;

        const campos = ['nome_completo', 'rg', 'telefone', 'telefone2', 'email', 'endereco', 'cidade', 'estado', 'cep'];
        const compradorIdInput = document.getElementById('comprador_id');

        cpfInput.addEventListener('blur', function () {
            const cpf = onlyDigits(cpfInput.value);
            if (cpf.length !== 11) return;

            const url = baseUrl + '/pages/vendas/cadastrar.php?ajax=buscar_cpf&cpf=' + encodeURIComponent(cpf);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.encontrado) {
                        if (compradorIdInput) compradorIdInput.value = data.id;
                        campos.forEach(function (name) {
                            const el = document.getElementById(name);
                            if (el && data[name] !== undefined) {
                                el.value = data[name] || '';
                            }
                        });
                    } else {
                        if (compradorIdInput) compradorIdInput.value = '';
                        campos.forEach(function (name) {
                            if (name === 'nome_completo') return;
                            const el = document.getElementById(name);
                            if (el) el.value = '';
                        });
                    }
                })
                .catch(function () {
                    console.warn('Erro ao buscar comprador por CPF.');
                });
        });
    }

    // -------------------------------------------------------------------------
    // Upload múltiplo com preview
    // -------------------------------------------------------------------------

    function initUploadWizard() {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('documentos');
        const fileList = document.getElementById('file-list');

        if (!dropzone || !fileInput || !fileList) return;

        let selectedFiles = [];

        function renderFileList() {
            fileList.innerHTML = '';
            selectedFiles.forEach(function (item, index) {
                const div = document.createElement('div');
                div.className = 'file-item';

                let preview = '<i class="bi bi-file-earmark"></i>';
                if (item.file.type.startsWith('image/')) {
                    preview = '<img class="file-preview" src="' + URL.createObjectURL(item.file) + '" alt="">';
                }

                div.innerHTML =
                    preview +
                    '<span class="file-name">' + item.file.name + ' (' + formatFileSize(item.file.size) + ')</span>' +
                    '<input type="text" class="form-control file-desc-input" name="descricoes[]" placeholder="Descrição (ex: Nota fiscal)" value="' + (item.descricao || '') + '">' +
                    '<button type="button" class="file-remove" data-index="' + index + '" title="Remover"><i class="bi bi-x-lg"></i></button>';

                const descInput = div.querySelector('.file-desc-input');
                descInput.addEventListener('input', function () {
                    selectedFiles[index].descricao = descInput.value;
                });

                div.querySelector('.file-remove').addEventListener('click', function () {
                    selectedFiles.splice(index, 1);
                    syncFileInput();
                    renderFileList();
                });

                fileList.appendChild(div);
            });
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        function addFiles(files) {
            const maxSize = 10 * 1024 * 1024;
            const allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'doc'];

            Array.from(files).forEach(function (file) {
                if (selectedFiles.length >= 10) {
                    alert('Máximo de 10 arquivos por venda.');
                    return;
                }
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowed.includes(ext)) {
                    alert('Tipo não permitido: ' + file.name);
                    return;
                }
                if (file.size > maxSize) {
                    alert('Arquivo muito grande (máx 10MB): ' + file.name);
                    return;
                }
                selectedFiles.push({ file: file, descricao: '' });
            });

            syncFileInput();
            renderFileList();
        }

        function syncFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(function (item) {
                dt.items.add(item.file);
            });
            fileInput.files = dt.files;
        }

        dropzone.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            addFiles(fileInput.files);
        });

        dropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', function () {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            addFiles(e.dataTransfer.files);
        });
    }

    // -------------------------------------------------------------------------
    // Confirmação de exclusão de documento
    // -------------------------------------------------------------------------

    function showModal(msg, onConfirm) {
        const overlay = document.getElementById('modal-overlay');
        if (!overlay) { if (confirm(msg)) onConfirm(); return; }
        document.getElementById('modal-body').textContent = msg;
        overlay.hidden = false;
        const confirmBtn = document.getElementById('modal-confirm');
        const cancelBtn = document.getElementById('modal-cancel');
        function close() { overlay.hidden = true; confirmBtn.onclick = null; cancelBtn.onclick = null; }
        cancelBtn.onclick = close;
        confirmBtn.onclick = function () { close(); onConfirm(); };
    }

    function initConfirmDelete() {
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                const msg = el.dataset.confirm || 'Tem certeza?';
                const form = el.closest('form');
                showModal(msg, function () {
                    if (form) form.submit();
                    else if (el.href) window.location = el.href;
                });
            });
        });
    }

    function initMobileMenu() {
        const btn = document.getElementById('btn-menu-mobile');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!btn || !sidebar) return;
        function toggle(open) {
            sidebar.classList.toggle('open', open);
            if (overlay) overlay.classList.toggle('open', open);
        }
        btn.addEventListener('click', function () { toggle(!sidebar.classList.contains('open')); });
        if (overlay) overlay.addEventListener('click', function () { toggle(false); });
    }

    function initViaCep() {
        const cep = document.getElementById('cep');
        if (!cep) return;
        cep.addEventListener('blur', function () {
            const d = onlyDigits(cep.value);
            if (d.length !== 8) return;
            fetch('https://viacep.com.br/ws/' + d + '/json/')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.erro) return;
                    const end = document.getElementById('endereco');
                    const cid = document.getElementById('cidade');
                    const uf = document.getElementById('estado');
                    if (end) end.value = data.logradouro || '';
                    if (cid) cid.value = data.localidade || '';
                    if (uf) uf.value = data.uf || '';
                }).catch(function () {});
        });
    }

    function initReservaFields() {
        const status = document.getElementById('status');
        if (!status) return;
        function toggle() {
            const show = status.value === 'reservado';
            ['reserva-fields', 'reserva-ate-field', 'valor-sinal-field'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.style.display = show ? 'block' : 'none';
            });
        }
        status.addEventListener('change', toggle);
        toggle();
    }

    function initCharts() {
        if (!window.Chart || !window.ENZO_CHARTS) return;
        const orange = '#E8510A';
        const labels = window.ENZO_CHARTS.vendas.map(function (v) { return v.mes; });
        const data = window.ENZO_CHARTS.vendas.map(function (v) { return parseFloat(v.total); });
        const el1 = document.getElementById('chart-vendas');
        if (el1) new Chart(el1, { type: 'line', data: { labels: labels, datasets: [{ label: 'R$', data: data, borderColor: orange, tension: 0.3, fill: false }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        const el2 = document.getElementById('chart-marcas');
        if (el2) new Chart(el2, { type: 'bar', data: { labels: window.ENZO_CHARTS.marcas.map(function (m) { return m.marca; }), datasets: [{ data: window.ENZO_CHARTS.marcas.map(function (m) { return parseFloat(m.total); }), backgroundColor: orange }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        const el3 = document.getElementById('chart-pagamento');
        if (el3) new Chart(el3, { type: 'doughnut', data: { labels: window.ENZO_CHARTS.pagamento.map(function (p) { return p.forma_pagamento; }), datasets: [{ data: window.ENZO_CHARTS.pagamento.map(function (p) { return p.qtd; }), backgroundColor: ['#E8510A','#1F3040','#1a7a4a','#b45309','#6B7280','#be123c'] }] }, options: { responsive: true, maintainAspectRatio: false } });
    }

    function apiUrl(path) {
        const base = (window.ENZO_BASE_URL || '/').replace(/\/?$/, '/');
        return base + path.replace(/^\//, '');
    }

    function initCpfReveal() {
        document.querySelectorAll('[data-reveal-cpf]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = document.getElementById(btn.dataset.revealCpf);
                const compradorId = btn.dataset.compradorId;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="csrf_token"]')?.value;

                if (!compradorId || !csrf || !target) return;

                btn.disabled = true;
                const body = new FormData();
                body.append('comprador_id', compradorId);
                body.append('csrf_token', csrf);

                fetch(apiUrl('pages/api/revelar-cpf.php'), {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: body,
                })
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                    .then(function (data) {
                        if (data.cpf) {
                            target.textContent = data.cpf;
                            btn.style.display = 'none';
                        } else {
                            btn.disabled = false;
                        }
                    })
                    .catch(function () { btn.disabled = false; });
            });
        });
    }

    function initCancelVenda() {
        const btn = document.getElementById('btn-cancelar-venda');
        const form = document.getElementById('form-cancelar-venda');
        const motivoInput = document.getElementById('cancelar-motivo');
        if (!btn || !form || !motivoInput) return;

        btn.addEventListener('click', function () {
            const overlay = document.getElementById('modal-overlay');
            const modalBody = document.getElementById('modal-body');
            if (!overlay || !modalBody) return;

            modalBody.innerHTML = '<label for="modal-motivo" style="display:block;margin-bottom:8px;">Motivo do cancelamento:</label>'
                + '<textarea id="modal-motivo" class="form-control" rows="3" required></textarea>';
            overlay.hidden = false;

            const confirmBtn = document.getElementById('modal-confirm');
            const cancelBtn = document.getElementById('modal-cancel');
            function close() {
                overlay.hidden = true;
                modalBody.textContent = '';
                confirmBtn.onclick = null;
                cancelBtn.onclick = null;
            }
            cancelBtn.onclick = close;
            confirmBtn.onclick = function () {
                const motivo = document.getElementById('modal-motivo')?.value?.trim();
                if (!motivo) return;
                motivoInput.value = motivo;
                close();
                form.submit();
            };
        });
    }

    // -------------------------------------------------------------------------
    // Impressão
    // -------------------------------------------------------------------------

    function initPrint() {
        const btn = document.getElementById('btn-print');
        if (btn) {
            btn.addEventListener('click', function () {
                window.print();
            });
        }
    }

    // -------------------------------------------------------------------------
    // Inicialização
    // -------------------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', function () {
        initMoedaMasks();
        initCpfMask();
        initTelefoneMask();
        initCepMask();
        initImeiValidation();
        initMargemCalculo();
        initFormaPagamento();
        initWizard();
        initCelularSearch();
        initBuscaComprador();
        initUploadWizard();
        initConfirmDelete();
        initPrint();
        initMobileMenu();
        initViaCep();
        initReservaFields();
        initCharts();
        initCpfReveal();
        initCancelVenda();
    });
})();
