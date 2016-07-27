const prefs = {};

function debounce(func, wait, immediate) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        const later = () => {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

prefs.toggleCheckboxes = (type, list) => {
    const inputs = list.querySelectorAll('input');
    Array.prototype.map.call(inputs, input => {
        input.checked = type === 'all';
    });
};

prefs.addMultiSelects = (label, list) => {
    if (label.getElementsByClassName("pref-multiselect").length < 1) {
        const options = ["all", "none"];
        const container = document.createElement("div");
        container.className = "pref-multiselect";

        options.map(type => {
            const el = document.createElement("a");
            el.href = "#";
            el.textContent = type;

            el.addEventListener("click", e => {
                e.preventDefault();
                prefs.toggleCheckboxes(type, list);
            });

            container.appendChild(el);
        });
        label.appendChild(container);
    }
};

prefs.watchTextInputChanges = () => {
    const textInputs = document.querySelectorAll(".input-text");
    function isDirty(input) {
        if (input.value) {
            input.offsetParent.classList.add("dirty");
        } else {
            input.offsetParent.classList.remove("dirty");
        }
    }
    Array.prototype.map.call(textInputs, input => {
        isDirty(input);
        input.onfocus = () => {
            input.offsetParent.classList.add("focused");
        };
        input.onblur = () => {
            input.offsetParent.classList.remove("focused");
        };
        input.addEventListener("keyup", e => {
            isDirty(e.target);
        });
    });
};
prefs.watchUnsub = () => {
    const elem = document.getElementById("input-unsub");
    const prefsHeight = debounce(() => {
        const prefs = document.querySelector(".prefs");
        prefs.style.height = "auto";
        prefs.style.height = `${prefs.scrollHeight}px`;
    }, 250);

    prefsHeight();

    window.addEventListener('resize', prefsHeight);

    if (elem.checked) {
        elem.form.classList.add("is-unsub");
    }
    const isChecked = () => {
        if (elem.checked) {
            elem.form.classList.add("is-unsub","foldup");
        } else {
            elem.form.classList.remove("is-unsub","foldup");
        }
    };
    elem.addEventListener("change", isChecked);
};



prefs.init = () => {
    const prefSection = document.getElementsByClassName("pref-section");
    Array.prototype.map.call(prefSection, section => {
        const label = section.getElementsByClassName("pref-label--container")[0];
        const list = section.getElementsByClassName("pref-list--container")[0];
        if (label) {
            prefs.addMultiSelects(label, list);
        }
    });
    prefs.watchTextInputChanges();
    prefs.watchUnsub();
};

((() => {
    document.documentElement.className = "js";
    prefs.init();
})());
