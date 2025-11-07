(function () {
  const payload = window.ET_TRANSLATION_PAYLOAD || {};

  document.addEventListener('DOMContentLoaded', function () {
    const listContainer = document.getElementById('et-translations-list');
    const entryIdField = document.getElementById('et-entry-id');
    const originalField = document.getElementById('et-original-field');
    const translationField = document.getElementById('et-translation-field');
    const searchField = document.getElementById('et-search-input');
    const popover = document.getElementById('et-search-popover');
    const views = document.querySelectorAll('.et-view[data-et-view]');
    const toggleButtons = document.querySelectorAll('.et-toggle-btn');

    if (!listContainer || !entryIdField || !originalField || !translationField || !searchField || !popover) {
      return;
    }

    const labels = payload.labels || {};
    const badgeText = labels.pending || 'Pending change';
    const emptyText = labels.noResults || 'No translations found';
    const contextLabelText = labels.context || 'Context';

    const MAX_RESULTS = 20;

    const translations = Array.isArray(payload.translations)
      ? payload.translations.map(function (item) {
          return {
            id: item.id,
            original: item.original || '',
            translation: item.translation || '',
            context: item.context || ''
          };
        })
      : [];

    const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
      ? new Intl.Collator(undefined, { sensitivity: 'base' })
      : null;

    const getSortKey = function (entry) {
      const candidate = entry.translation && entry.translation.trim()
        ? entry.translation
        : entry.original;
      return (candidate || '').toLowerCase();
    };

    translations.sort(function (a, b) {
      if (collator) {
        return collator.compare(getSortKey(a), getSortKey(b));
      }
      return getSortKey(a).localeCompare(getSortKey(b));
    });

    const translationById = new Map();
    translations.forEach(function (entry) {
      translationById.set(entry.id, entry);
    });

    const stagedIds = Array.isArray(payload.staged) ? new Set(payload.staged) : new Set();

    let activeId = payload.activeId && translationById.has(payload.activeId)
      ? payload.activeId
      : (translations[0] && translations[0].id) || null;

    let currentSearchTerm = '';
    let currentView = 'form';
    let popoverIndex = -1;

    const getPopoverItems = function () {
      return popover.querySelectorAll('.et-search-item');
    };

    const openPopover = function () {
      popover.hidden = false;
      searchField.setAttribute('aria-expanded', 'true');
    };

    const closePopover = function () {
      popover.innerHTML = '';
      popover.hidden = true;
      popoverIndex = -1;
      searchField.setAttribute('aria-expanded', 'false');
      searchField.removeAttribute('aria-activedescendant');
    };

    const matchesTerm = function (entry, term) {
      if (!term) {
        return true;
      }
      const needle = term.toLowerCase();
      return (
        (entry.original && entry.original.toLowerCase().includes(needle)) ||
        (entry.translation && entry.translation.toLowerCase().includes(needle)) ||
        (entry.context && entry.context.toLowerCase().includes(needle))
      );
    };

    const updateActiveIndicator = function () {
      const buttons = listContainer.querySelectorAll('.et-list-item');
      buttons.forEach(function (button) {
        button.classList.toggle('is-active', button.dataset.entryId === activeId);
      });
    };

    const applyPopoverHighlight = function (index) {
      const items = getPopoverItems();
      if (!items.length) {
        searchField.removeAttribute('aria-activedescendant');
        popoverIndex = -1;
        return;
      }

      const clampedIndex = Math.max(0, Math.min(index, items.length - 1));
      popoverIndex = clampedIndex;

      items.forEach(function (item, itemIndex) {
        const isHighlighted = itemIndex === clampedIndex;
        item.classList.toggle('is-highlighted', isHighlighted);
        if (isHighlighted) {
          searchField.setAttribute('aria-activedescendant', item.id);
          item.scrollIntoView({ block: 'nearest' });
        }
      });
    };

    const syncSearchActive = function () {
      if (popover.hidden) {
        return;
      }

      const items = getPopoverItems();
      if (!items.length) {
        return;
      }

      let activeFound = false;
      items.forEach(function (item, index) {
        const isActive = item.dataset.entryId === activeId;
        item.classList.toggle('is-active', isActive);
        if (isActive) {
          activeFound = true;
          popoverIndex = index;
        }
      });

      if (!activeFound) {
        popoverIndex = popoverIndex >= 0 && popoverIndex < items.length ? popoverIndex : 0;
      }

      applyPopoverHighlight(popoverIndex);
    };

    const createListButton = function (entry) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'et-list-item';
      button.dataset.entryId = entry.id;

      if (stagedIds.has(entry.id)) {
        button.classList.add('has-change');
      }

      const title = document.createElement('h3');
      title.textContent = entry.original;
      button.appendChild(title);

      const preview = document.createElement('p');
      preview.textContent = entry.translation;
      preview.dataset.preview = 'true';
      button.appendChild(preview);

      if (entry.context) {
        const context = document.createElement('div');
        context.className = 'et-list-context';

        const contextLabel = document.createElement('span');
        contextLabel.className = 'et-list-context-label';
        contextLabel.textContent = contextLabelText + ':';
        context.appendChild(contextLabel);

        const contextValue = document.createElement('span');
        contextValue.className = 'et-list-context-value';
        contextValue.textContent = entry.context;
        context.appendChild(contextValue);

        button.appendChild(context);
      }

      if (stagedIds.has(entry.id)) {
        const badge = document.createElement('span');
        badge.className = 'et-list-badge';
        badge.textContent = badgeText;
        button.appendChild(badge);
      }

      button.addEventListener('click', function () {
        activateEntry(entry.id, true);
        setView('form');
      });

      return button;
    };

    const renderFullList = function () {
      listContainer.innerHTML = '';

      if (!translations.length) {
        const emptyRow = document.createElement('div');
        emptyRow.className = 'et-empty';
        emptyRow.textContent = emptyText;
        listContainer.appendChild(emptyRow);
        return;
      }

      translations.forEach(function (entry) {
        listContainer.appendChild(createListButton(entry));
      });

      updateActiveIndicator();
    };

    const renderSearchResults = function (term) {
      currentSearchTerm = term;

      const cleanTerm = term.trim();
      if (!cleanTerm) {
        closePopover();
        return;
      }

      const matches = translations.filter(function (entry) {
        return matchesTerm(entry, cleanTerm);
      });

      popover.innerHTML = '';
      const content = document.createElement('div');
      content.className = 'et-search-popover-content';
      popover.appendChild(content);

      if (!matches.length) {
        const emptyEl = document.createElement('div');
        emptyEl.className = 'et-search-empty';
        emptyEl.textContent = emptyText;
        content.appendChild(emptyEl);
        openPopover();
        searchField.removeAttribute('aria-activedescendant');
        popoverIndex = -1;
        return;
      }

      matches.slice(0, MAX_RESULTS).forEach(function (entry) {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'et-search-item';
        item.dataset.entryId = entry.id;
        item.id = 'et-search-option-' + entry.id;
        item.setAttribute('role', 'option');

        if (stagedIds.has(entry.id)) {
          item.classList.add('has-change');
        }

        const title = document.createElement('span');
        title.className = 'et-search-item__title';
        title.textContent = entry.original;
        item.appendChild(title);

        const preview = document.createElement('span');
        preview.className = 'et-search-item__preview';
        preview.dataset.preview = 'true';
        preview.textContent = entry.translation;
        item.appendChild(preview);

        if (entry.context) {
          const context = document.createElement('span');
          context.className = 'et-search-item__context';
          context.textContent = entry.context;
          item.appendChild(context);
        }

        if (stagedIds.has(entry.id)) {
          const badge = document.createElement('span');
          badge.className = 'et-search-item__badge';
          badge.textContent = badgeText;
          item.appendChild(badge);
        }

        item.addEventListener('mouseenter', function () {
          const options = getPopoverItems();
          const index = Array.prototype.indexOf.call(options, item);
          if (index >= 0) {
            applyPopoverHighlight(index);
          }
        });

        item.addEventListener('click', function () {
          activateEntry(entry.id, true);
          closePopover();
        });

        content.appendChild(item);
      });

      openPopover();
      syncSearchActive();
    };

    const activateEntry = function (id, focus) {
      const entry = translationById.get(id);
      if (!entry) {
        return;
      }

      activeId = id;
      entryIdField.value = id;
      originalField.value = entry.original;
      translationField.value = entry.translation;

      updateActiveIndicator();
      syncSearchActive();

      if (focus) {
        translationField.focus();
      }
    };

    const setView = function (view) {
      currentView = view;
      views.forEach(function (element) {
        element.classList.toggle('et-view-active', element.dataset.etView === view);
      });
      toggleButtons.forEach(function (button) {
        button.classList.toggle('et-toggle-btn--active', button.dataset.etView === view);
      });

      if (view !== 'form') {
        closePopover();
      }
    };

    let searchTimer = null;
    searchField.addEventListener('input', function (event) {
      const value = event.target.value || '';
      if (searchTimer) {
        window.clearTimeout(searchTimer);
      }
      searchTimer = window.setTimeout(function () {
        renderSearchResults(value);
      }, 120);
    });

    searchField.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown') {
        if (popover.hidden) {
          renderSearchResults(searchField.value || '');
        }
        const items = getPopoverItems();
        if (!items.length) {
          return;
        }
        event.preventDefault();
        applyPopoverHighlight(popoverIndex < 0 ? 0 : popoverIndex + 1);
      } else if (event.key === 'ArrowUp') {
        const items = getPopoverItems();
        if (!items.length) {
          return;
        }
        event.preventDefault();
        const nextIndex = popoverIndex <= 0 ? 0 : popoverIndex - 1;
        applyPopoverHighlight(nextIndex);
      } else if (event.key === 'Enter') {
        const items = getPopoverItems();
        if (!items.length || popoverIndex < 0 || popoverIndex >= items.length) {
          return;
        }
        event.preventDefault();
        items[popoverIndex].click();
      } else if (event.key === 'Escape') {
        if (!popover.hidden) {
          event.preventDefault();
          closePopover();
        }
      }
    });

    searchField.addEventListener('focus', function () {
      if (currentSearchTerm) {
        renderSearchResults(currentSearchTerm);
      }
    });

    document.addEventListener('mousedown', function (event) {
      if (popover.hidden) {
        return;
      }
      if (!event.target.closest('.et-search')) {
        closePopover();
      }
    });

    toggleButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        const view = button.dataset.etView || 'form';
        setView(view);
      });
    });

    translationField.addEventListener('input', function (event) {
      if (!activeId) {
        return;
      }
      const entry = translationById.get(activeId);
      if (!entry) {
        return;
      }
      entry.translation = event.target.value;

      const activePreview = listContainer.querySelector(
        '.et-list-item.is-active [data-preview="true"]'
      );
      if (activePreview) {
        activePreview.textContent = event.target.value;
      }

      if (!popover.hidden) {
        const activeOptionPreview = popover.querySelector(
          '.et-search-item[data-entry-id="' + activeId + '"] [data-preview="true"]'
        );
        if (activeOptionPreview) {
          activeOptionPreview.textContent = event.target.value;
        }
      }
    });

    setView('form');
    renderFullList();

    if (activeId) {
      activateEntry(activeId, false);
    } else {
      entryIdField.value = '';
      originalField.value = '';
      translationField.value = '';
    }

    if (currentSearchTerm) {
      renderSearchResults(currentSearchTerm);
    }
  });
})();
