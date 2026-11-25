/**
 * Asistente de consulta y Agente de Decisión (DSS) sin recargar la página.
 *
 * Seguridad: el servidor devuelve fragmentos HTML ya escapados por Blade y RagFormato;
 * el texto del usuario se pinta solo con textContent. Si algo falla (validación,
 * sesión vencida, red), el formulario se envía de forma tradicional.
 */

import Chart from 'chart.js/auto';

const NIVELES_UMBRAL = [
    { valor: 40, etiqueta: 'Alto' },
    { valor: 65, etiqueta: 'Crítico' },
];

const MENSAJES_ESPERA = [
    'Buscando en las fuentes autorizadas…',
    'Contrastando con los datos de la cartera…',
    'Verificando las citas…',
    'Redactando la respuesta…',
];

/** Líneas discontinuas en los umbrales de nivel (40 = Alto, 65 = Crítico). */
const pluginUmbrales = {
    id: 'sigUmbrales',
    afterDatasetsDraw(chart) {
        const { ctx, chartArea, scales } = chart;
        ctx.save();
        ctx.setLineDash([4, 4]);
        ctx.lineWidth = 1;
        ctx.font = '600 10px "Segoe UI", sans-serif';
        ctx.textAlign = 'center';
        for (const u of NIVELES_UMBRAL) {
            const x = scales.x.getPixelForValue(u.valor);
            ctx.strokeStyle = 'rgba(100, 116, 139, 0.55)';
            ctx.beginPath();
            ctx.moveTo(x, chartArea.top);
            ctx.lineTo(x, chartArea.bottom);
            ctx.stroke();
            ctx.fillStyle = '#64748b';
            ctx.fillText(u.etiqueta, x, chartArea.top - 4);
        }
        ctx.restore();
    },
};

export function dibujarRanking(raiz = document) {
    raiz.querySelectorAll('canvas[data-grafico-ranking]').forEach((lienzo) => {
        if (lienzo.dataset.dibujado) return;
        let datos;
        try {
            datos = JSON.parse(lienzo.dataset.graficoRanking);
        } catch {
            return;
        }
        lienzo.dataset.dibujado = '1';
        const reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        new Chart(lienzo, {
            type: 'bar',
            data: {
                labels: datos.map((d) => d.codigo),
                datasets: [{
                    data: datos.map((d) => d.puntaje),
                    backgroundColor: datos.map((d) => d.color),
                    borderRadius: 4,
                    borderSkipped: false,
                    barThickness: 18,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: reducirMovimiento ? false : { duration: 900, easing: 'easeOutQuart' },
                layout: { padding: { top: 14, right: 8 } },
                scales: {
                    x: {
                        min: 0,
                        max: 100,
                        ticks: { stepSize: 20, color: '#64748b', font: { size: 10 } },
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                        border: { display: false },
                    },
                    y: {
                        ticks: { color: '#0f172a', font: { size: 11, weight: '600', family: '"Cascadia Code", Consolas, monospace' } },
                        grid: { display: false },
                        border: { display: false },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#001836',
                        padding: 10,
                        callbacks: {
                            title: (items) => {
                                const d = datos[items[0].dataIndex];
                                return `${d.codigo} · ${d.nombre}`;
                            },
                            label: (item) => ` ${datos[item.dataIndex].nivel}: ${item.parsed.x}/100`,
                        },
                    },
                },
            },
            plugins: [pluginUmbrales],
        });
    });
}

/** Envía el formulario y devuelve el fragmento HTML, o null si hay que recurrir al envío tradicional. */
async function enviarParcial(form) {
    const respuesta = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: { 'X-Asistente-Parcial': '1', Accept: 'text/html' },
    });
    if (respuesta.status === 429) {
        return { limite: true };
    }
    if (respuesta.redirected || !respuesta.ok) {
        return null;
    }
    return { html: await respuesta.text() };
}

function envioTradicional(form) {
    // submit() no dispara el evento "submit", así que no se repite la vía AJAX.
    HTMLFormElement.prototype.submit.call(form);
}

function iniciarChat() {
    const chat = document.querySelector('[data-chat]');
    if (!chat) return;

    const log = chat.querySelector('[data-chat-log]');
    const form = chat.querySelector('[data-chat-form]');
    const campo = form.querySelector('textarea[name="pregunta"]');
    const boton = form.querySelector('[data-chat-enviar]');
    const contador = form.querySelector('[data-chat-contador]');
    const tplEscribiendo = document.getElementById('tpl-escribiendo');
    const tplAviso = document.getElementById('tpl-aviso');
    const limite = chat.dataset.limiteMinuto;
    let ocupado = false;

    const ajustarCampo = () => {
        campo.style.height = 'auto';
        campo.style.height = `${Math.min(campo.scrollHeight, 160)}px`;
        contador.textContent = String(campo.value.length);
    };

    const alFinal = () => {
        log.scrollTop = log.scrollHeight;
    };

    const agregarUsuario = (texto) => {
        const fila = document.createElement('div');
        fila.className = 'flex justify-end sig-burbuja-entrada';
        fila.dataset.mensaje = 'usuario';
        const burbuja = document.createElement('div');
        burbuja.className = 'max-w-[36rem] rounded-2xl rounded-tr-md bg-brand-800 text-white px-4 py-2.5 text-sm leading-relaxed shadow-panel whitespace-pre-line break-words';
        burbuja.textContent = texto;
        fila.appendChild(burbuja);
        log.appendChild(fila);
    };

    const agregarAviso = (texto) => {
        const nodo = tplAviso.content.firstElementChild.cloneNode(true);
        nodo.querySelector('[data-aviso-texto]').textContent = texto;
        log.appendChild(nodo);
    };

    campo.addEventListener('input', ajustarCampo);
    campo.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    form.querySelectorAll('[data-sugerencia]').forEach((b) => {
        b.addEventListener('click', () => {
            campo.value = b.dataset.sugerencia;
            ajustarCampo();
            form.requestSubmit();
        });
    });

    chat.querySelector('[data-chat-reiniciar]')?.addEventListener('click', () => {
        log.querySelectorAll('[data-mensaje]').forEach((n) => n.remove());
        campo.value = '';
        ajustarCampo();
        campo.focus();
    });

    form.addEventListener('submit', async (e) => {
        const texto = campo.value.trim();
        if (!form.checkValidity() || texto === '') return; // deja actuar la validación nativa
        e.preventDefault();
        if (ocupado) return;
        ocupado = true;
        boton.disabled = true;

        agregarUsuario(texto);
        const escribiendo = tplEscribiendo.content.firstElementChild.cloneNode(true);
        log.appendChild(escribiendo);
        alFinal();

        const textoEspera = escribiendo.querySelector('[data-escribiendo-texto]');
        let paso = 0;
        const rotador = setInterval(() => {
            paso = Math.min(paso + 1, MENSAJES_ESPERA.length - 1);
            textoEspera.textContent = MENSAJES_ESPERA[paso];
        }, 1400);

        try {
            const r = await enviarParcial(form);
            if (r === null) {
                envioTradicional(form);
                return;
            }
            escribiendo.remove();
            if (r.limite) {
                agregarAviso(`Alcanzó el límite de ${limite} consultas por minuto. Es una protección contra abuso del servicio: espere un momento y vuelva a intentarlo.`);
            } else {
                const contenedor = document.createElement('div');
                contenedor.innerHTML = r.html;
                const nuevo = contenedor.firstElementChild;
                if (nuevo) {
                    log.appendChild(nuevo);
                    // Desplaza solo la conversación (no la página) hasta el inicio de la respuesta.
                    const desplazamiento = nuevo.getBoundingClientRect().top - log.getBoundingClientRect().top - 12;
                    log.scrollBy({ top: desplazamiento, behavior: 'smooth' });
                }
                campo.value = '';
                ajustarCampo();
            }
        } catch {
            escribiendo.remove();
            agregarAviso('No se pudo contactar con el servidor. Revise la conexión e inténtelo de nuevo.');
        } finally {
            clearInterval(rotador);
            ocupado = false;
            boton.disabled = false;
            campo.focus();
        }
    });

    ajustarCampo();
    alFinal();
}

function iniciarAgente() {
    const form = document.querySelector('[data-agente-form]');
    if (!form) return;

    const destino = document.querySelector('[data-agente-resultado]');
    const pasos = [...document.querySelectorAll('[data-agente-pipeline] .sig-paso')];
    const boton = form.querySelector('[data-agente-enviar]');
    const textoBoton = form.querySelector('[data-agente-texto]');
    const textoOriginal = textoBoton.textContent;
    let ocupado = false;

    const marcar = (indiceActivo, todosHechos = false) => {
        pasos.forEach((p, i) => {
            p.dataset.estado = todosHechos || i < indiceActivo ? 'hecho' : i === indiceActivo ? 'activo' : 'pendiente';
        });
    };

    form.addEventListener('submit', async (e) => {
        if (!form.checkValidity()) return;
        e.preventDefault();
        if (ocupado) return;
        ocupado = true;
        boton.disabled = true;
        textoBoton.textContent = 'Analizando cartera…';
        destino.querySelector('[data-dictamen]')?.classList.add('opacity-40');

        let activo = 0;
        marcar(activo);
        const avance = setInterval(() => {
            activo = Math.min(activo + 1, pasos.length - 2); // el control humano se marca al terminar
            marcar(activo);
        }, 700);
        const minimo = new Promise((ok) => setTimeout(ok, 1600)); // deja ver el recorrido del agente

        try {
            const [r] = await Promise.all([enviarParcial(form), minimo]);
            if (r === null) {
                envioTradicional(form);
                return;
            }
            clearInterval(avance);
            if (r.limite) {
                marcar(-1);
                destino.innerHTML = '';
                const aviso = document.createElement('div');
                aviso.className = 'rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900';
                aviso.textContent = 'Alcanzó el límite de ejecuciones por minuto (protección contra abuso). Espere un momento.';
                destino.appendChild(aviso);
                return;
            }
            marcar(pasos.length, true);
            destino.innerHTML = r.html;
            dibujarRanking(destino);
            destino.firstElementChild?.scrollIntoView({ block: 'start', behavior: 'smooth' });
        } catch {
            marcar(-1);
            destino.querySelector('[data-dictamen]')?.classList.remove('opacity-40');
        } finally {
            clearInterval(avance);
            ocupado = false;
            boton.disabled = false;
            textoBoton.textContent = textoOriginal;
        }
    });
}

function iniciar() {
    iniciarChat();
    iniciarAgente();
    dibujarRanking();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
} else {
    iniciar();
}
