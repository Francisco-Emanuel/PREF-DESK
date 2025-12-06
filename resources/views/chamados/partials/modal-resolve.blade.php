<x-modal name="resolve-chamado-modal" focusable>
    <form id="resolve-chamado-form" method="post" action="{{ route('chamados.resolve', $chamado) }}" class="p-6">
        @csrf
        @method('patch')

        <h2 class="text-lg font-medium text-gray-900">
            Registrar Solução do Chamado #{{ $chamado->id }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Descreva a solução aplicada e colete as assinaturas para confirmar o atendimento.
        </p>

        {{-- Descrição da Solução --}}
        <div class="mt-6">
            <x-input-label for="solucao_final" value="Descrição da Solução" />
            <textarea id="solucao_final" name="solucao_final" rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required
                minlength="10">{{ old('solucao_final') }}</textarea>
        </div>

        {{-- Painel de Assinaturas (Técnico e Solicitante) --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Assinatura do Técnico --}}
            <div>
                <x-input-label for="signature-pad-tecnico" value="Assinatura do Técnico" />
                <div class="mt-1 border border-gray-300 rounded-md">
                    <canvas id="signature-pad-tecnico" class="w-full h-32"></canvas>
                </div>
                <button type="button" data-clear-for="tecnico"
                    class="text-sm text-blue-600 hover:underline mt-1">Limpar</button>
                <input type="hidden" name="assinatura_tecnico" id="assinatura_tecnico_input">
            </div>

            {{-- Assinatura do Solicitante --}}
            <div>
                <x-input-label for="signature-pad-solicitante" value="Assinatura do Solicitante" />
                <div class="mt-1 border border-gray-300 rounded-md">
                    <canvas id="signature-pad-solicitante" class="w-full h-32"></canvas>
                </div>
                <button type="button" data-clear-for="solicitante"
                    class="text-sm text-blue-600 hover:underline mt-1">Limpar</button>
                <input type="hidden" name="assinatura_solicitante" id="assinatura_solicitante_input">
            </div>
        </div>

        <div class="mt-6">
            <label for="servico_executado" class="flex items-center">
                <input type="checkbox" id="servico_executado" name="servico_executado"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm" required>
                <span class="ms-2 text-sm text-gray-700">Confirmo que o serviço foi executado e o problema
                    resolvido.</span>
            </label>
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
            <x-primary-button type="button" id="submit-resolve-form" class="ms-3">
                Confirmar Resolução
            </x-primary-button>
        </div>
    </form>
</x-modal>

{{-- Script para inicializar os Signature Pads --}}
@push('scripts')
    <script type="module">
        document.addEventListener('open-modal', (event) => {
            if (event.detail === 'resolve-chamado-modal') {
                const canvasTecnico = document.getElementById('signature-pad-tecnico');
                const canvasSolicitante = document.getElementById('signature-pad-solicitante');

                
                const initSignaturePad = (canvas) => {
                    if (canvas && !canvas.signaturePad) {
                        const signaturePad = new window.SignaturePad(canvas, {
                            backgroundColor: 'rgb(255, 255, 255)'
                        });
                        canvas.signaturePad = signaturePad;
                    }
                };

                initSignaturePad(canvasTecnico);
                initSignaturePad(canvasSolicitante);

                
                document.querySelector('[data-clear-for="tecnico"]').addEventListener('click', () => {
                    canvasTecnico.signaturePad.clear();
                });
                document.querySelector('[data-clear-for="solicitante"]').addEventListener('click', () => {
                    canvasSolicitante.signaturePad.clear();
                });

                document.getElementById('submit-resolve-form').addEventListener('click', () => {


                    if (!canvasTecnico.signaturePad.isEmpty()) {
                        document.getElementById('assinatura_tecnico_input').value = canvasTecnico.signaturePad.toDataURL('image/png');
                    } else {
                        document.getElementById('assinatura_tecnico_input').value = '';
                    }

                    if (!canvasSolicitante.signaturePad.isEmpty()) {
                        document.getElementById('assinatura_solicitante_input').value = canvasSolicitante.signaturePad.toDataURL('image/png');
                    } else {
                        document.getElementById('assinatura_solicitante_input').value = '';
                    }

                    document.getElementById('resolve-chamado-form').submit();
                });
            }
        });
    </script>
@endpush