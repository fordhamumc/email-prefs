"use strict";
var prefs = {};

prefs.toggleCheckboxes = function (type, list) {
    var inputs = list.querySelectorAll('input');
    Array.prototype.map.call(inputs, function (input) {
        input.checked = type === 'all';
    });
};

prefs.addMultiSelects = function (list) {
    if (list.getElementsByClassName("pref-multiselect").length < 1) {
        var options = ["all", "none"];
        var container = document.createElement("div");
        container.className = "pref-multiselect";
        options.map(function (type) {
            var el = document.createElement("a");
            el.href = "#";
            el.innerText = type;

            el.addEventListener("click", function (e) {
                e.preventDefault();
                prefs.toggleCheckboxes(type, list);
            });

            container.appendChild(el);
        });
        list.insertBefore(container, list.firstChild);
    }
};

prefs.createOptionsContainer = function (list, label) {
    if (list.getElementsByClassName("pref-title").length < 1) {
        var header = document.createElement("h3");
        header.className = "pref-title";
        header.innerText = label.innerText;
        list.insertBefore(header, list.firstChild);
    }

    if (list.getElementsByClassName("pref-footer").length < 1) {
        var closeButton = document.createElement("button");
        closeButton.innerText = "Close";
        closeButton.addEventListener("click", function (e) {
            e.preventDefault();
            prefs.closeOptions(list.parentElement);
        });

        var footer = document.createElement("footer");
        footer.className = "pref-footer";
        footer.appendChild(closeButton);
        list.appendChild(footer);
    }
};

prefs.clickOff = function (e) {
    if (e.target.classList.contains("pref-list--toggle")) {
        prefs.closeOptions(e.target);
    }
};

prefs.closeOptions = function (listContainer) {
    prefs.updateSelected(listContainer);
    listContainer.removeEventListener("click", prefs.clickOff);
    document.body.classList.remove("modal-open");
    listContainer.parentElement.classList.remove("active");
};

prefs.openOptions = function (listContainer) {
    document.body.classList.add("modal-open");
    listContainer.addEventListener("click", prefs.clickOff);
    listContainer.classList.add("active");
    listContainer.querySelector(".pref-list").scrollTop = 0;
};

prefs.linkLabel = function (label, prefSections) {
    var link = document.createElement("a");
    link.href = "#";
    link.innerText = label.innerText;
    link.addEventListener("click", function (e) {
        var currentSection = label.parentElement;
        e.preventDefault();
        Array.prototype.map.call(prefSections, function (section) {
            section.classList.remove("active");
        });
        prefs.openOptions(currentSection);
    });
    label.innerText = '';
    label.appendChild(link);
};

prefs.updateSelected = function (listContainer) {
    var selected = listContainer.querySelectorAll("input:checked");
    var parent = listContainer.parentElement;
    var container = parent.querySelector(".pref-selected") || document.createElement("ul");
    container.className = "pref-selected";
    container.innerHTML = "";

    Array.prototype.map.call(selected, function (input, index) {
        var item = document.createElement("li");
        item.className = "pref-selected-item";
        item.innerText = input.parentElement.innerText.trim() + ((index < selected.length - 1) ? ', ' : '');
        container.appendChild(item);
    });
    parent.appendChild(container);
};

prefs.watchTextInputChanges = function (textInputs) {
    function isDirty(input) {
        var parent = input.parentElement;
        if (input.value) {
            parent.classList.add("dirty");
        } else {
            parent.classList.remove("dirty");
        }
    }
    Array.prototype.map.call(textInputs, function (input) {
        isDirty(input);
        var parent = input.parentElement;
        input.onfocus = function () {
            parent.classList.add("focused");
        };
        input.onblur = function () {
            parent.classList.remove("focused");
        };
        input.addEventListener("keyup", function (e) {
            isDirty(e.target);
        });
    });
};

prefs.init = function () {
    var prefSections = document.getElementsByClassName("pref-section");
    var textInputs = document.querySelectorAll(".input-text");
    Array.prototype.map.call(prefSections, function (section) {
        var label = section.getElementsByClassName("pref-label")[0];
        var list = section.getElementsByClassName("pref-list")[0];
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

(function () {
    document.documentElement.className = "js";
    prefs.init();
}());
