// =========================================================
// CONTROLE FINANCEIRO — interações da interface
// =========================================================

// 1. Menu lateral no celular
const lateral = document.getElementById("lateral");
const botaoMenu = document.getElementById("botaoMenu");
const sombra = document.getElementById("sombraMenu");

function alternarMenu(abrir) {
  lateral.classList.toggle("aberto", abrir);
  sombra.classList.toggle("visivel", abrir);
  botaoMenu.setAttribute("aria-expanded", abrir);
}
if (botaoMenu) {
  botaoMenu.addEventListener("click", () => alternarMenu(!lateral.classList.contains("aberto")));
  sombra.addEventListener("click", () => alternarMenu(false));
}

// 2. Confirmação antes de ações destrutivas (botões com data-confirmar)
document.querySelectorAll("[data-confirmar]").forEach((botao) => {
  botao.addEventListener("click", (evento) => {
    if (!window.confirm(botao.dataset.confirmar)) {
      evento.preventDefault();
    }
  });
});

// 3. Formulário: mostra a data de quitação só quando "já foi pago" estiver marcado
const caixaQuitado = document.getElementById("quitado");
const campoQuitacao = document.getElementById("campoQuitacao");
if (caixaQuitado && campoQuitacao) {
  caixaQuitado.addEventListener("change", () => {
    campoQuitacao.hidden = !caixaQuitado.checked;
  });
}

// 4. Máscara de moeda: o usuário digita só números e o campo formata (ex.: 123456 → 1.234,56)
document.querySelectorAll("[data-moeda]").forEach((campo) => {
  campo.addEventListener("input", () => {
    const numeros = campo.value.replace(/\D/g, "").replace(/^0+/, "");
    if (!numeros) {
      campo.value = "";
      return;
    }
    const centavos = numeros.padStart(3, "0");
    const inteiro = centavos.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    campo.value = `${inteiro},${centavos.slice(-2)}`;
  });
});

// 5. Login: preencher a conta de demonstração
const preencherDemo = document.getElementById("preencherDemo");
if (preencherDemo) {
  preencherDemo.addEventListener("click", () => {
    document.querySelector('input[name="email"]').value = preencherDemo.dataset.email;
    document.querySelector('input[name="senha"]').value = "demo123";
    document.querySelector('input[name="senha"]').focus();
  });
}

// 6. Mensagens de sucesso somem sozinhas depois de alguns segundos
document.querySelectorAll(".alerta-sucesso").forEach((alerta) => {
  setTimeout(() => {
    alerta.style.transition = "opacity 0.5s";
    alerta.style.opacity = "0";
    setTimeout(() => alerta.remove(), 500);
  }, 4500);
});

// 7. Gráficos do painel (Chart.js)
const areaGraficos = document.getElementById("graficos");
if (areaGraficos && window.Chart) {
  const dados = JSON.parse(areaGraficos.dataset.graficos);
  const moeda = (v) => v.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

  Chart.defaults.color = "#a3a3bd";
  Chart.defaults.font.family = "Poppins, sans-serif";
  Chart.defaults.borderColor = "#25253a";

  // 7.1 Rosca: despesas por categoria
  const canvasCategorias = document.getElementById("graficoCategorias");
  if (canvasCategorias) {
    new Chart(canvasCategorias, {
      type: "doughnut",
      data: {
        labels: dados.categorias.rotulos,
        datasets: [{
          data: dados.categorias.valores,
          backgroundColor: dados.categorias.cores,
          borderColor: "#161625",
          borderWidth: 3,
          hoverOffset: 8,
        }],
      },
      options: {
        maintainAspectRatio: false,
        cutout: "62%",
        plugins: {
          legend: { position: "right", labels: { boxWidth: 12, padding: 12 } },
          tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${moeda(ctx.parsed)}` } },
        },
      },
    });
  }

  // 7.2 Barras: a pagar × a receber nos últimos 6 meses
  new Chart(document.getElementById("graficoEvolucao"), {
    type: "bar",
    data: {
      labels: dados.evolucao.rotulos,
      datasets: [
        { label: "A pagar", data: dados.evolucao.pagar, backgroundColor: "#f87171", borderRadius: 6, maxBarThickness: 28 },
        { label: "A receber", data: dados.evolucao.receber, backgroundColor: "#34d399", borderRadius: 6, maxBarThickness: 28 },
      ],
    },
    options: {
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { callback: (v) => "R$ " + v.toLocaleString("pt-BR") } },
        x: { grid: { display: false } },
      },
      plugins: {
        legend: { labels: { boxWidth: 12 } },
        tooltip: { callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${moeda(ctx.parsed.y)}` } },
      },
    },
  });
}
