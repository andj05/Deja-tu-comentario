// JavaScript adicional para mejorar la experiencia de usuario
document.addEventListener("DOMContentLoaded", function () {
  // Referencias a elementos del DOM
  const nombreInput = document.getElementById("nombre");
  const comentarioTextarea = document.getElementById("comentario");
  const form = document.getElementById("comentarioForm");

  // Validación en tiempo real
  function validarCampo(campo) {
    const valor = campo.value.trim();

    if (valor === "") {
      campo.classList.add("is-invalid");
      campo.classList.remove("is-valid");
      return false;
    } else {
      campo.classList.add("is-valid");
      campo.classList.remove("is-invalid");
      return true;
    }
  }

  // Eventos de validación
  nombreInput.addEventListener("blur", function () {
    validarCampo(this);
  });

  nombreInput.addEventListener("input", function () {
    // Limpiar validación mientras escribe
    this.classList.remove("is-invalid", "is-valid");
  });

  comentarioTextarea.addEventListener("blur", function () {
    validarCampo(this);
  });

  comentarioTextarea.addEventListener("input", function () {
    // Limpiar validación mientras escribe
    this.classList.remove("is-invalid", "is-valid");
  });

  // Prevenir envío múltiple
  let enviandoFormulario = false;

  form.addEventListener("submit", function (e) {
    if (enviandoFormulario) {
      e.preventDefault();
      return false;
    }

    enviandoFormulario = true;

    // Resetear después de un tiempo
    setTimeout(() => {
      enviandoFormulario = false;
    }, 3000);
  });

  // Efectos visuales para los comentarios
  function animarComentarios() {
    const comentarios = document.querySelectorAll(".comment-card");
    comentarios.forEach((comentario, index) => {
      comentario.style.opacity = "0";
      comentario.style.transform = "translateY(20px)";

      setTimeout(() => {
        comentario.style.transition = "all 0.5s ease";
        comentario.style.opacity = "1";
        comentario.style.transform = "translateY(0)";
      }, index * 100);
    });
  }

  // Observer para detectar cuando se agregan nuevos comentarios
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1 && node.classList.contains("comment-card")) {
            // Animar nuevo comentario
            node.style.opacity = "0";
            node.style.transform = "translateY(20px)";

            setTimeout(() => {
              node.style.transition = "all 0.5s ease";
              node.style.opacity = "1";
              node.style.transform = "translateY(0)";
            }, 100);
          }
        });
      }
    });
  });

  // Observar cambios en la lista de comentarios
  const comentariosLista = document.getElementById("comentarios-lista");
  if (comentariosLista) {
    observer.observe(comentariosLista, {
      childList: true,
      subtree: true,
    });
  }

  // Auto-resize del textarea
  comentarioTextarea.addEventListener("input", function () {
    this.style.height = "auto";
    this.style.height = this.scrollHeight + "px";
  });

  // Placeholder dinámico
  const placeholders = [
    "Escriba aquí su comentario...",
    "Comparta su experiencia...",
    "¿Qué opina sobre esto?...",
    "Sus comentarios son valiosos...",
    "Cuéntenos su opinión...",
  ];

  let placeholderIndex = 0;

  function cambiarPlaceholder() {
    if (
      comentarioTextarea.value === "" &&
      document.activeElement !== comentarioTextarea
    ) {
      comentarioTextarea.placeholder = placeholders[placeholderIndex];
      placeholderIndex = (placeholderIndex + 1) % placeholders.length;
    }
  }

  // Cambiar placeholder cada 4 segundos
  setInterval(cambiarPlaceholder, 4000);

  // Detectar conexión a internet
  function verificarConexion() {
    const conexionIndicador = document.createElement("div");
    conexionIndicador.id = "conexion-status";
    conexionIndicador.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            display: none;
        `;
    document.body.appendChild(conexionIndicador);

    function mostrarEstadoConexion(online) {
      if (online) {
        conexionIndicador.style.backgroundColor = "#28a745";
        conexionIndicador.innerHTML = '<i class="fas fa-wifi"></i> Conectado';
      } else {
        conexionIndicador.style.backgroundColor = "#dc3545";
        conexionIndicador.innerHTML =
          '<i class="fas fa-wifi"></i> Sin conexión';
      }

      conexionIndicador.style.display = "block";

      setTimeout(() => {
        conexionIndicador.style.display = "none";
      }, 3000);
    }

    window.addEventListener("online", () => mostrarEstadoConexion(true));
    window.addEventListener("offline", () => mostrarEstadoConexion(false));
  }

  verificarConexion();

  // Confirmación antes de salir con datos sin guardar
  let formularioModificado = false;

  nombreInput.addEventListener("input", () => (formularioModificado = true));
  comentarioTextarea.addEventListener(
    "input",
    () => (formularioModificado = true)
  );

  form.addEventListener("submit", () => (formularioModificado = false));

  window.addEventListener("beforeunload", function (e) {
    if (
      formularioModificado &&
      (nombreInput.value.trim() || comentarioTextarea.value.trim())
    ) {
      e.preventDefault();
      e.returnValue =
        "Tiene cambios sin guardar. ¿Está seguro de que desea salir?";
      return e.returnValue;
    }
  });

  // Botón para refrescar comentarios
  function agregarBotonRefresh() {
    const refreshBtn = document.createElement("button");
    refreshBtn.className = "btn btn-outline-secondary btn-sm ms-2";
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
    refreshBtn.onclick = function () {
      this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
      this.disabled = true;

      // Llamar a la función global de cargar comentarios
      if (window.cargarComentarios) {
        window
          .cargarComentarios()
          .then(() => {
            this.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
            this.disabled = false;
          })
          .catch(() => {
            this.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
            this.disabled = false;
            mostrarToast("Error al actualizar comentarios", "danger");
          });
      }
    };

    // Agregar botón al header de comentarios
    const commentsHeader = document.querySelector(".comments-section h2");
    if (commentsHeader && !document.querySelector(".btn-outline-secondary")) {
      commentsHeader.appendChild(refreshBtn);
    }
  }

  // Función para mostrar toast notifications
  function mostrarToast(mensaje, tipo = "success") {
    // Crear container de toasts si no existe
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
      toastContainer = document.createElement("div");
      toastContainer.id = "toast-container";
      toastContainer.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1000;
                max-width: 350px;
            `;
      document.body.appendChild(toastContainer);
    }

    // Crear toast
    const toast = document.createElement("div");
    const toastId = "toast-" + Date.now();
    toast.id = toastId;
    toast.className = `toast align-items-center text-white bg-${
      tipo === "success" ? "success" : tipo === "danger" ? "danger" : "info"
    } border-0 mb-2`;
    toast.setAttribute("role", "alert");
    toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${
                      tipo === "success"
                        ? "check-circle"
                        : tipo === "danger"
                        ? "exclamation-triangle"
                        : "info-circle"
                    } me-2"></i>
                    ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

    toastContainer.appendChild(toast);

    // Inicializar y mostrar toast
    const bsToast = new bootstrap.Toast(toast, {
      autohide: true,
      delay: 4000,
    });
    bsToast.show();

    // Limpiar después de que se oculte
    toast.addEventListener("hidden.bs.toast", function () {
      toast.remove();
    });
  }

  // Hacer función global
  window.mostrarToast = mostrarToast;

  // Lazy loading para comentarios
  function implementarLazyLoading() {
    const comentarios = document.querySelectorAll(".comment-card");

    const observerLazy = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            observerLazy.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.1,
        rootMargin: "50px",
      }
    );

    comentarios.forEach((comentario) => {
      if (!comentario.classList.contains("visible")) {
        comentario.style.opacity = "0";
        comentario.style.transform = "translateY(20px)";
        comentario.style.transition = "all 0.6s ease";
        observerLazy.observe(comentario);
      }
    });
  }

  // Auto-refresh cada 30 segundos
  let autoRefreshInterval;

  function iniciarAutoRefresh() {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
    }

    autoRefreshInterval = setInterval(() => {
      if (window.cargarComentarios && document.visibilityState === "visible") {
        window.cargarComentarios().catch(console.error);
      }
    }, 30000); // 30 segundos
  }

  function detenerAutoRefresh() {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
    }
  }

  // Función para exportar comentarios
  function exportarComentarios() {
    const comentarios = document.querySelectorAll(".comment-card");
    if (comentarios.length === 0) {
      mostrarToast("No hay comentarios para exportar", "info");
      return;
    }

    let contenido =
      "Comentarios exportados - " + new Date().toLocaleString() + "\n\n";

    comentarios.forEach((comentario, index) => {
      const autor =
        comentario.querySelector(".comment-author")?.textContent.trim() ||
        "Anónimo";
      const fecha =
        comentario.querySelector(".text-muted")?.textContent.trim() ||
        "Sin fecha";
      const texto =
        comentario
          .querySelector(".comment-content p")
          ?.textContent.replace(/"/g, "")
          .trim() || "Sin contenido";

      contenido += `${index + 1}. ${autor}\n`;
      contenido += `   Fecha: ${fecha}\n`;
      contenido += `   Comentario: ${texto}\n\n`;
    });

    // Crear y descargar archivo
    try {
      const blob = new Blob([contenido], { type: "text/plain;charset=utf-8" });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.style.display = "none";
      a.href = url;
      a.download =
        "comentarios_" + new Date().toISOString().split("T")[0] + ".txt";
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);

      mostrarToast("Comentarios exportados exitosamente", "success");
    } catch (error) {
      console.error("Error al exportar:", error);
      mostrarToast("Error al exportar comentarios", "danger");
    }
  }

  // Agregar botón de exportar
  function agregarBotonExportar() {
    const exportBtn = document.createElement("button");
    exportBtn.className = "btn btn-outline-info btn-sm ms-2";
    exportBtn.innerHTML = '<i class="fas fa-download"></i> Exportar';
    exportBtn.onclick = exportarComentarios;
    exportBtn.title = "Exportar comentarios a archivo de texto";

    const commentsHeader = document.querySelector(".comments-section h2");
    if (commentsHeader && !document.querySelector(".btn-outline-info")) {
      commentsHeader.appendChild(exportBtn);
    }
  }

  // Función para buscar comentarios
  function implementarBusqueda() {
    const searchContainer = document.createElement("div");
    searchContainer.className = "mb-3";
    searchContainer.innerHTML = `
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control" id="buscar-comentarios" 
                       placeholder="Buscar en comentarios...">
                <button class="btn btn-outline-secondary" type="button" id="limpiar-busqueda">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

    const commentsSection = document.querySelector(".comments-section");
    const commentsList = document.getElementById("comentarios-lista");

    if (commentsSection && commentsList) {
      commentsSection.insertBefore(searchContainer, commentsList);

      const searchInput = document.getElementById("buscar-comentarios");
      const clearBtn = document.getElementById("limpiar-busqueda");

      searchInput.addEventListener("input", function () {
        const termino = this.value.toLowerCase().trim();
        const comentarios = document.querySelectorAll(".comment-card");

        comentarios.forEach((comentario) => {
          const autor =
            comentario
              .querySelector(".comment-author")
              ?.textContent.toLowerCase() || "";
          const texto =
            comentario
              .querySelector(".comment-content p")
              ?.textContent.toLowerCase() || "";

          if (
            termino === "" ||
            autor.includes(termino) ||
            texto.includes(termino)
          ) {
            comentario.style.display = "block";
          } else {
            comentario.style.display = "none";
          }
        });
      });

      clearBtn.addEventListener("click", function () {
        searchInput.value = "";
        const comentarios = document.querySelectorAll(".comment-card");
        comentarios.forEach((comentario) => {
          comentario.style.display = "block";
        });
      });
    }
  }

  // CSS adicional
  const style = document.createElement("style");
  style.textContent = `
        .comment-card.visible {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
        
        .comment-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        #toast-container {
            max-width: 350px;
        }
        
        @media (max-width: 768px) {
            #toast-container {
                bottom: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
    `;
  document.head.appendChild(style);

  // Inicializar funciones cuando la página cargue completamente
  window.addEventListener("load", function () {
    setTimeout(() => {
      agregarBotonRefresh();
      agregarBotonExportar();
      implementarLazyLoading();
      implementarBusqueda();
      iniciarAutoRefresh();
    }, 1000);
  });

  // Manejar visibilidad de la página para auto-refresh
  document.addEventListener("visibilitychange", function () {
    if (document.visibilityState === "visible") {
      iniciarAutoRefresh();
    } else {
      detenerAutoRefresh();
    }
  });

  // Cleanup al salir
  window.addEventListener("beforeunload", function () {
    detenerAutoRefresh();
  });

  console.log("Sistema de comentarios JavaScript cargado correctamente");
});
