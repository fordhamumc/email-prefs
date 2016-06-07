const prefs = {};

prefs.toggleCheckboxes = (type, list) => {
    const inputs = list.querySelectorAll('input');
    Array.prototype.map.call(inputs, input => {
        input.checked = type === 'all';
});
};

prefs.addMultiSelects = list => {
    if (list.getElementsByClassName("pref-multiselect").length < 1) {
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
        list.insertBefore(container, list.firstChild);
    }
};

prefs.createOptionsContainer = (list, label) => {
    if (list.getElementsByClassName("pref-title").length < 1) {
        const header = document.createElement("h3");
        header.className = "pref-title";
        header.textContent = label.textContent;
        list.insertBefore(header, list.firstChild);
    }

    if (list.getElementsByClassName("pref-footer").length < 1) {
        const closeButton = document.createElement("button");
        closeButton.textContent = "Close";
        closeButton.addEventListener("click", e => {
            e.preventDefault();
        prefs.closeOptions(list.parentElement);
    });

        const footer = document.createElement("footer");
        footer.className = "pref-footer";
        footer.appendChild(closeButton);
        list.appendChild(footer);
    }
};

prefs.clickOff = e => {
    if (e.target.classList.contains("pref-list--toggle")) {
        prefs.closeOptions(e.target);
    }
};

prefs.escapeOff = () => {
    prefs.closeOptions(document.querySelector(".active .pref-list--toggle"));
};

prefs.closeOptions = listContainer => {
    prefs.updateSelected(listContainer);
    listContainer.removeEventListener("click", prefs.clickOff);
    document.removeEventListener("keyup", prefs.escapeOff);
    document.body.classList.remove("modal-open");
    listContainer.parentElement.classList.remove("active");
};

prefs.openOptions = listContainer => {
    document.body.classList.add("modal-open");
    listContainer.addEventListener("click", prefs.clickOff);
    document.addEventListener("keyup", prefs.escapeOff);
    listContainer.classList.add("active");
    listContainer.querySelector(".pref-list").scrollTop = 0;
};

prefs.linkLabel = (label, prefSections) => {
    const link = document.createElement("a");
    link.href = "#";
    link.textContent = label.textContent;
    link.addEventListener("click", e => {
        const currentSection = label.parentElement;
    e.preventDefault();
    Array.prototype.map.call(prefSections, section => {
        section.classList.remove("active");
});
    prefs.openOptions(currentSection);
});
    label.textContent = '';
    label.appendChild(link);
};

prefs.updateSelected = listContainer => {
    const selected = listContainer.querySelectorAll("input:checked");
    const parent = listContainer.parentElement;
    const container = parent.querySelector(".pref-selected") || document.createElement("ul");
    container.className = "pref-selected";
    container.innerHTML = "";

    Array.prototype.map.call(selected, (input, index) => {
        const item = document.createElement("li");
    item.className = "pref-selected-item";
    item.textContent = input.parentElement.textContent.trim() + ((index < selected.length - 1) ? ', ' : '');
    container.appendChild(item);
});
    parent.appendChild(container);
};

prefs.watchTextInputChanges = textInputs => {
    function isDirty(input) {
        const parent = input.parentElement;
        if (input.value) {
            parent.classList.add("dirty");
        } else {
            parent.classList.remove("dirty");
        }
    }
    Array.prototype.map.call(textInputs, input => {
        isDirty(input);
        const parent = input.parentElement;
        input.onfocus = () => {
            parent.classList.add("focused");
        };
        input.onblur = () => {
            parent.classList.remove("focused");
        };
        input.addEventListener("keyup", e => {
            isDirty(e.target);
        });
    });
};

prefs.init = () => {
    const prefSections = document.getElementsByClassName("pref-section");
    const textInputs = document.querySelectorAll(".input-text");
    Array.prototype.map.call(prefSections, section => {
        const label = section.getElementsByClassName("pref-label")[0];
    const list = section.getElementsByClassName("pref-list")[0];
    if (list) {
        list.parentElement.classList.add('pref-list--toggle');
        prefs.addMultiSelects(list);
        prefs.updateSelected(list.parentElement);
    }
    if (label) {
        prefs.linkLabel(label, prefSections);
    }
    if (list && label) {
        prefs.createOptionsContainer(list, label);
    }
});
    prefs.watchTextInputChanges(textInputs);
};

((() => {
    document.documentElement.className = "js";
prefs.init();
})());
