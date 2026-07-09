(function () {
    var live_region = document.createElement("div");
    live_region.className = "sr-only";
    live_region.setAttribute("aria-live", "polite");
    live_region.setAttribute("aria-atomic", "true");
    document.body.appendChild(live_region);

    function get_snippet_text(button) {
        var encoded = button.getAttribute("data-copy");
        if (encoded) {
            return encoded.replace(/\\n/g, "\n");
        }

        var snippet = button.closest(".code-snippet");
        if (!snippet) {
            return "";
        }

        var body = snippet.querySelector(".code-snippet__body");
        if (!body) {
            return "";
        }

        var lines = body.querySelectorAll(".code-line");
        if (lines.length) {
            return Array.prototype.map.call(lines, function (line) {
                if (line.classList.contains("code-line--blank")) {
                    return "";
                }

                var prompt = line.querySelector(".tok-prompt");
                var text = line.textContent || "";
                if (prompt) {
                    text = text.replace(/^\$\s*/, "");
                }

                return text;
            }).join("\n").replace(/\n+$/, "");
        }

        return body.innerText.trim();
    }

    function announce(message) {
        live_region.textContent = message;
    }

    function set_copy_feedback(button, message) {
        var original = button.getAttribute("data-copy-label") || button.textContent.trim();
        button.textContent = message;
        button.disabled = true;
        announce(message);

        window.setTimeout(function () {
            button.textContent = original;
            button.disabled = false;
        }, 2000);
    }

    function copy_text(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            try {
                var area = document.createElement("textarea");
                area.value = text;
                area.setAttribute("readonly", "");
                area.style.position = "fixed";
                area.style.left = "-9999px";
                document.body.appendChild(area);
                area.select();
                document.execCommand("copy");
                document.body.removeChild(area);
                resolve();
            } catch (error) {
                reject(error);
            }
        });
    }

    document.querySelectorAll(".code-snippet__copy").forEach(function (button) {
        if (!button.getAttribute("data-copy-label")) {
            button.setAttribute("data-copy-label", button.textContent.trim());
        }

        button.setAttribute("aria-label", "Copiar código al portapapeles");

        button.addEventListener("click", function () {
            var text = get_snippet_text(button);

            copy_text(text).then(function () {
                set_copy_feedback(button, "Copiado");
            }).catch(function () {
                set_copy_feedback(button, "Error");
            });
        });
    });

    document.querySelectorAll(".code-snippet__body").forEach(function (body) {
        body.addEventListener("dblclick", function () {
            var range = document.createRange();
            range.selectNodeContents(body);
            var selection = window.getSelection();
            if (!selection) {
                return;
            }

            selection.removeAllRanges();
            selection.addRange(range);
        });
    });

    var header = document.querySelector(".header");
    var progress_bar = document.querySelector(".header__progress-bar");
    var nav_links = document.querySelectorAll(".header__link[href^='#']");
    var sections = [];
    var quickstart_steps = document.querySelectorAll(".quickstart-step");

    nav_links.forEach(function (link) {
        var id = link.getAttribute("href").slice(1);
        var section = document.getElementById(id);
        if (section) {
            sections.push({ id: id, el: section, link: link });
        }
    });

    var step_map = {
        install: document.getElementById("composer-demo"),
        env: document.getElementById("env-type-demo"),
        bootstrap: document.getElementById("bootstrap-demo"),
        routes: document.getElementById("routes-demo"),
        views: document.getElementById("views-demo"),
        models: document.getElementById("models-demo"),
        controllers: document.getElementById("controllers-demo"),
        middleware: document.getElementById("middleware-demo"),
        run: document.getElementById("shell-demo")
    };

    function update_scroll_ui() {
        var scroll_top = window.scrollY || document.documentElement.scrollTop;
        var doc_height = document.documentElement.scrollHeight - window.innerHeight;
        var progress = doc_height > 0 ? Math.min(1, scroll_top / doc_height) : 0;

        if (progress_bar) {
            progress_bar.style.width = (progress * 100).toFixed(2) + "%";
        }

        if (header) {
            header.classList.toggle("header--scrolled", scroll_top > 12);
        }

        var offset = (header ? header.offsetHeight : 72) + 24;
        var current_id = "";

        sections.forEach(function (item) {
            if (item.el.getBoundingClientRect().top <= offset) {
                current_id = item.id;
            }
        });

        nav_links.forEach(function (link) {
            var active = link.getAttribute("href") === "#" + current_id;
            link.classList.toggle("header__link--active", active);
        });

        quickstart_steps.forEach(function (step) {
            step.classList.remove("quickstart-step--active");
        });

        var active_step = null;
        Object.keys(step_map).forEach(function (key) {
            var target = step_map[key];
            if (!target) {
                return;
            }

            if (target.getBoundingClientRect().top <= offset + 40) {
                active_step = key;
            }
        });

        if (active_step) {
            var step_el = document.querySelector('.quickstart-step[data-step="' + active_step + '"]');
            if (step_el) {
                step_el.classList.add("quickstart-step--active");
            }
        }
    }

    var scroll_tick = false;

    window.addEventListener("scroll", function () {
        if (scroll_tick) {
            return;
        }

        scroll_tick = true;
        window.requestAnimationFrame(function () {
            update_scroll_ui();
            scroll_tick = false;
        });
    }, { passive: true });

    update_scroll_ui();
})();