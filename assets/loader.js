/**
 * loader.js — Portfolio Data Loader
 * Fetches data/portfolio.json and renders all dynamic content.
 * This bridges the admin dashboard to the live portfolio pages.
 *
 * Works on: index.html, academic.html, skill.html, experience.html, project.html
 */
(function () {
  'use strict';

  /* =========================================================
     SVG ICON LIBRARY
     Keys match the "icon" field in portfolio.json skills.
     Add new keys here to support custom icons in the dashboard.
  ========================================================= */
  var ICONS = {
    grid:       '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>',
    wind:       '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
    layers:     '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    factory:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
    star:       '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
    chart:      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    users:      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
    graduation: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
    flask:      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>',
    code:       '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
    tool:       '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    bolt:       '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
    default:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'
  };

  function makeSvg(key, size) {
    size = size || '18px';
    return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:' + size + ';height:' + size + ';display:inline;vertical-align:-3px;margin-right:6px;color:var(--teal);">' + (ICONS[key] || ICONS.default) + '</svg>';
  }

  function makeSvgRaw(key, size) {
    size = size || '22px';
    return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:' + size + ';height:' + size + ';">' + (ICONS[key] || ICONS.default) + '</svg>';
  }

  /* =========================================================
     DOM HELPERS
  ========================================================= */
  function $id(id) { return document.getElementById(id); }

  function applyText(id, val) {
    var e = $id(id);
    if (e && val != null) e.textContent = val;
  }

  function applyHTML(id, html) {
    var e = $id(id);
    if (e && html != null) e.innerHTML = html;
  }

  /* Resolve dot-path like "labels.index.aboutTitle" on an object */
  function resolve(obj, path) {
    return path.split('.').reduce(function (o, k) { return o && o[k]; }, obj);
  }

  /* =========================================================
     LABELS — applies data-label="section.key" everywhere
  ========================================================= */
  function applyLabels(data) {
    if (!data.labels) return;

    /* Generic data-label attributes */
    document.querySelectorAll('[data-label]').forEach(function (elem) {
      var val = resolve(data.labels, elem.getAttribute('data-label'));
      if (val != null) elem.textContent = val;
    });

    /* Nav brand */
    if (data.labels.nav && data.labels.nav.brand) {
      document.querySelectorAll('.nav-brand').forEach(function (e) {
        e.textContent = data.labels.nav.brand;
      });
    }

    /* Footer plain text (no links) */
    if (data.labels.footer && data.labels.footer.text) {
      document.querySelectorAll('footer p').forEach(function (e) {
        if (!e.querySelector('a')) e.textContent = data.labels.footer.text;
      });
    }
  }

  /* =========================================================
     RE-TRIGGER ANIMATIONS for dynamically added elements
  ========================================================= */
  function reObserve() {
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          entry.target.querySelectorAll('.progress-fill').forEach(function (bar) {
            bar.style.width = (bar.dataset.width || 0) + '%';
          });
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    document.querySelectorAll('.animate:not(.visible)').forEach(function (el) {
      var rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight * 0.9) {
        /* Already in viewport — animate immediately */
        el.classList.add('visible');
        el.querySelectorAll('.progress-fill').forEach(function (bar) {
          bar.style.width = (bar.dataset.width || 0) + '%';
        });
      } else {
        obs.observe(el);
      }
    });
  }

  /* =========================================================
     INDEX PAGE
  ========================================================= */
  function renderIndex(data) {
    var p = data.personal;
    var L = data.labels || {};

    /* Hero tagline — prefer personal.tagline (set by dashboard) */
    var heroH1 = $id('hero-tagline');
    if (heroH1) {
      var tagline = p.tagline || (L.hero && L.hero.tagline) || '';
      if (tagline) heroH1.innerHTML = tagline.replace(/\n/g, '<br>');
    }

    /* Hero roles — prefer personal.title (set by dashboard) */
    var rolesEl = $id('hero-roles');
    if (rolesEl) {
      var rolesStr = p.title || (L.hero && L.hero.roles) || '';
      if (rolesStr) {
        var parts = rolesStr.split('|').map(function (s) { return s.trim(); });
        rolesEl.innerHTML = parts.map(function (r, i) {
          return '<span>' + r + '</span>' + (i < parts.length - 1 ? ' &nbsp;|&nbsp; ' : '');
        }).join('');
      }
    }

    /* About text */
    var aboutEl = $id('about-text');
    if (aboutEl && p.about) {
      aboutEl.innerHTML = p.about.replace(/\n\n/g, '<br><br>');
    }

    /* Hero photo */
    if (p.photo) {
      var heroImg = document.querySelector('.hero-photo-card img');
      if (heroImg) { heroImg.src = p.photo; heroImg.alt = p.name || ''; }
    }

    /* Hard skill chips */
    if ($id('skills-hard-chips') && data.skills && data.skills.hard) {
      applyHTML('skills-hard-chips', data.skills.hard.map(function (s) {
        return '<span class="chip">' + s.title + '</span>';
      }).join(''));
    }

    /* Soft skill chips */
    if ($id('skills-soft-chips') && data.skills && data.skills.soft) {
      applyHTML('skills-soft-chips', data.skills.soft.map(function (s) {
        return '<span class="chip soft">' + s.title + '</span>';
      }).join(''));
    }

    /* Testimonials */
    var testGrid = $id('testimonials-grid');
    if (testGrid && data.testimonials && data.testimonials.length) {
      testGrid.innerHTML = data.testimonials.map(function (t) {
        return '<div class="testimonial-card">' +
          '<p class="testimonial-quote">&ldquo;' + t.quote + '&rdquo;</p>' +
          '<div class="testimonial-author">— ' + t.name + '</div>' +
          '<div class="testimonial-role">' + t.title + '</div>' +
          '</div>';
      }).join('');
    }

    /* Featured projects (first 2) */
    var featGrid = $id('featured-projects-grid');
    if (featGrid && data.projects && data.projects.length) {
      featGrid.innerHTML = data.projects.slice(0, 2).map(function (proj) {
        return '<div class="summary-card">' +
          '<div class="tag-row" style="margin-bottom:0.75rem;">' +
          proj.tags.map(function (t) { return '<span class="tag">' + t + '</span>'; }).join('') +
          '</div>' +
          '<h4>' + proj.title + '</h4>' +
          '<p style="margin-top:0.4rem;">' + proj.headline + '</p>' +
          '<div style="margin-top:1rem;"><a href="project.html" class="link-teal">View Project ' +
          '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;">' +
          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>' +
          '</a></div>' +
          '</div>';
      }).join('');
    }

    /* Contact links */
    var emailLink = $id('contact-email');
    if (emailLink && p.email) {
      emailLink.href = 'mailto:' + p.email;
      var emailSpan = emailLink.querySelector('span');
      if (emailSpan) emailSpan.textContent = p.email;
    }
    var liLink = $id('contact-linkedin');
    if (liLink && p.linkedin) liLink.href = p.linkedin;
    var telLink = $id('contact-phone');
    if (telLink && p.phone) {
      telLink.href = 'tel:' + p.phone.replace(/[\s\-]/g, '');
      var telSpan = telLink.querySelector('span');
      if (telSpan) telSpan.textContent = p.phone;
    }

    /* Connect description */
    if (L.index && L.index.connectDesc) {
      var desc = document.querySelector('.contact-section > .container .animate p');
      if (desc) desc.textContent = L.index.connectDesc;
    }
  }

  /* =========================================================
     ACADEMIC PAGE
  ========================================================= */
  function renderAcademic(data) {
    var L = data.labels ? (data.labels.academic || {}) : {};
    var gpaStarSvg = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>';

    /* Education */
    var eduContainer = $id('education-container');
    if (eduContainer && data.academic && data.academic.education) {
      eduContainer.innerHTML = data.academic.education.map(function (edu) {
        var gpaHtml = edu.gpa ? '<div class="gpa-badge">' + gpaStarSvg + 'GPA ' + edu.gpa + '</div>' : '';
        var desc = edu.description ? edu.description.replace(/\n\n/g, '<br><br>') : '';
        return '<div class="animate academic-card">' +
          '<div class="academic-icon">' + makeSvgRaw(edu.icon || 'graduation', '26px') + '</div>' +
          '<div class="academic-card-header"><div>' +
          '<div class="academic-institution">' + edu.institution + '</div>' +
          '<div class="academic-role">' + edu.degree + '</div>' +
          '<div class="academic-meta">' + (data.personal.location || '') + '</div>' +
          gpaHtml +
          '</div><span class="academic-period">' + edu.period + '</span></div>' +
          '<div class="academic-desc">' + desc + '</div>' +
          '</div>';
      }).join('');
    }

    /* Research */
    var resContainer = $id('research-container');
    if (resContainer && data.academic && data.academic.research) {
      resContainer.innerHTML = data.academic.research.map(function (res) {
        var desc = res.description ? res.description.replace(/\n\n/g, '<br><br>') : '';
        var contribHtml = res.contribution ?
          '<div style="margin-bottom:0.75rem;">' +
          '<span style="font-size:0.82rem;color:var(--muted);font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">' + (L.contributionLabel || 'Contribution') + '</span>' +
          '<p style="margin-top:0.25rem;font-size:0.95rem;color:var(--dark);font-weight:500;">' + res.contribution + '</p>' +
          '</div>' : '';
        var paperHtml = res.paperLink ?
          '<a href="' + res.paperLink + '" target="_blank" rel="noopener" class="paper-link">' +
          '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>' +
          (L.viewPaperText || 'View Published Paper') + '</a>' : '';
        return '<div class="animate academic-card">' +
          '<div class="academic-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:26px;height:26px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg></div>' +
          '<div class="academic-card-header"><div>' +
          '<div class="academic-institution">' + res.title + '</div>' +
          '<div class="academic-role">' + res.role + '</div>' +
          '<div class="academic-meta">' + res.institution + '</div>' +
          '</div><span class="academic-period">' + res.period + '</span></div>' +
          contribHtml +
          '<div class="academic-desc">' + desc + '</div>' +
          paperHtml +
          '</div>';
      }).join('');
    }
  }

  /* =========================================================
     SKILL PAGE
  ========================================================= */
  function renderSkill(data) {
    var L = data.labels ? (data.labels.skill || {}) : {};
    var chevSvg = '<svg class="expand-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
    var certIconSvg = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

    /* Hard skills */
    var hardList = $id('hard-skills-list');
    if (hardList && data.skills && data.skills.hard) {
      hardList.innerHTML = data.skills.hard.map(function (skill) {
        var imgHtml = (skill.images && skill.images.length)
          ? '<div class="img-gallery">' + skill.images.map(function (src) {
              return '<img src="' + src + '" alt="' + skill.title + '">';
            }).join('') + '</div>'
          : '';
        var certHtml = skill.certLink
          ? '<a href="' + skill.certLink + '" target="_blank" rel="noopener" class="cert-link">' +
            certIconSvg + (L.viewCertText || 'View Certificate') + '</a>'
          : '';
        return '<div class="skill-card animate">' +
          '<div class="skill-card-header" onclick="toggleExpand(this)">' +
          '<div class="skill-info">' +
          '<span class="skill-type-badge">Hard Skill</span>' +
          '<div class="skill-title">' + makeSvg(skill.icon || 'tool') + skill.title + '</div>' +
          '<div class="progress-wrap">' +
          '<div class="progress-label"><span>' + (L.proficiencyLabel || 'Proficiency') + '</span><span>' + skill.proficiency + '%</span></div>' +
          '<div class="progress-bar"><div class="progress-fill" data-width="' + skill.proficiency + '"></div></div>' +
          '</div></div>' +
          '<button class="expand-btn" aria-label="Expand">' + chevSvg + '</button>' +
          '</div>' +
          '<div class="expand-panel"><div class="expand-panel-inner">' +
          '<p>' + skill.description + '</p>' +
          imgHtml + certHtml +
          '</div></div></div>';
      }).join('');
    }

    /* Soft skills */
    var softGrid = $id('soft-skills-grid');
    if (softGrid && data.skills && data.skills.soft) {
      softGrid.innerHTML = data.skills.soft.map(function (skill) {
        return '<div class="soft-card animate" onclick="toggleSoft(this)">' +
          '<div class="soft-icon">' + makeSvgRaw(skill.icon || 'star') + '</div>' +
          '<div class="soft-title">' + skill.title + '</div>' +
          '<p class="soft-desc">' + skill.description + '</p>' +
          '</div>';
      }).join('');
    }
  }

  /* =========================================================
     EXPERIENCE PAGE
  ========================================================= */
  function buildTimelineCard(exp, type) {
    var pinSvg  = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
    var calSvg  = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
    var chevSvg = '<svg class="tl-expand-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
    var toggleFn = (type === 'other') ? 'toggleOtherCard(this)' : 'toggleTlCard(this)';
    var locationHtml = exp.location ? '<div class="tl-location">' + pinSvg + ' ' + exp.location + '</div>' : '';
    return '<div class="timeline-item">' +
      '<div class="timeline-dot"></div>' +
      '<div class="timeline-card" onclick="' + toggleFn + '">' +
      '<div class="tl-header"><div>' +
      '<div class="tl-title">' + exp.title + '</div>' +
      '<div class="tl-org">' + exp.organization + '</div>' +
      locationHtml +
      '</div><div class="tl-meta-col">' +
      '<div class="tl-period">' + calSvg + ' ' + exp.period + '</div>' +
      chevSvg +
      '</div></div>' +
      '<div class="tl-desc-wrap"><div class="tl-desc">' + exp.description + '</div></div>' +
      '</div></div>';
  }

  function buildOtherCard(exp) {
    var badgeSvg = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>';
    var chevSvg = '<svg class="tl-expand-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;color:var(--teal);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
    return '<div class="other-card" onclick="toggleOtherCard(this)" style="cursor:pointer;flex-direction:column;align-items:stretch;">' +
      '<div style="display:flex;align-items:flex-start;gap:1rem;">' +
      '<div class="other-icon">' + badgeSvg + '</div>' +
      '<div style="flex:1;"><div style="display:flex;justify-content:space-between;align-items:center;"><div>' +
      '<div class="other-title">' + exp.title + '</div>' +
      '<div class="other-org">' + exp.organization + '</div>' +
      '<div class="other-year">' + exp.period + '</div>' +
      '</div>' + chevSvg + '</div></div></div>' +
      '<div class="tl-desc-wrap" style="padding-left:3.5rem;">' +
      '<div class="tl-desc">' + exp.description + '</div></div></div>';
  }

  function renderExperience(data) {
    var profTl = $id('professional-timeline');
    if (profTl && data.experiences && data.experiences.professional) {
      profTl.innerHTML = data.experiences.professional.map(function (e) {
        return buildTimelineCard(e, 'professional');
      }).join('');
    }
    var orgTl = $id('organisational-timeline');
    if (orgTl && data.experiences && data.experiences.organisational) {
      orgTl.innerHTML = data.experiences.organisational.map(function (e) {
        return buildTimelineCard(e, 'organisational');
      }).join('');
    }
    var otherTl = $id('other-timeline');
    if (otherTl && data.experiences && data.experiences.other) {
      otherTl.innerHTML = data.experiences.other.map(function (e) {
        return buildOtherCard(e);
      }).join('');
    }
  }

  /* =========================================================
     PROJECT PAGE
  ========================================================= */
  function renderProject(data) {
    if (!data.projects || !data.projects.length) return;

    /* Update global projects array used by openProjectModal */
    window.portfolioProjects = data.projects;

    /* Rebuild filter bar with unique tags from JSON */
    var filterBar = $id('filter-bar');
    if (filterBar) {
      var allTags = [];
      data.projects.forEach(function (p) {
        p.tags.forEach(function (t) {
          if (allTags.indexOf(t) === -1) allTags.push(t);
        });
      });
      allTags.sort();
      filterBar.innerHTML =
        '<span class="tag filter-tag active" data-tag="All">All</span>' +
        allTags.map(function (t) {
          return '<span class="tag filter-tag" data-tag="' + t + '">' + t + '</span>';
        }).join('');

      /* Re-attach filter click handlers */
      filterBar.querySelectorAll('.filter-tag').forEach(function (tag) {
        tag.addEventListener('click', function () {
          filterBar.querySelectorAll('.filter-tag').forEach(function (t) { t.classList.remove('active'); });
          tag.classList.add('active');
          var selected = tag.dataset.tag;
          document.querySelectorAll('.project-item').forEach(function (item) {
            var tags = item.dataset.tags ? item.dataset.tags.split(',') : [];
            item.style.display = (selected === 'All' || tags.indexOf(selected) !== -1) ? '' : 'none';
          });
        });
      });
    }

    /* Rebuild project timeline */
    var timeline = $id('projects-timeline-container');
    if (!timeline) return;

    /* Sort by year descending, track first card per year */
    var sortedProjects = data.projects.slice().sort(function (a, b) { return b.year - a.year; });
    var seenYears = {};
    timeline.innerHTML = sortedProjects.map(function (proj) {
      var showYear = !seenYears[proj.year];
      seenYears[proj.year] = true;
      var thumbHtml = proj.thumbnail
        ? '<img src="' + proj.thumbnail + '" alt="' + proj.title + '">'
        : '<div class="project-thumb-placeholder">' +
          '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' +
          '<span>Click to view details</span></div>';
      return '<div class="project-item" data-tags="' + proj.tags.join(',') + '">' +
        '<div class="timeline-dot"></div>' +
        (showYear ? '<div class="project-year-label">' + proj.year + '</div>' : '') +
        '<div class="project-card" onclick="openProjectModal(\'' + proj.id + '\')">' +
        '<div class="project-thumb">' + thumbHtml +
        '<span class="project-expand-hint">↗ View Details</span></div>' +
        '<div class="project-body">' +
        '<div class="tag-row" style="margin-bottom:0.75rem;">' +
        proj.tags.map(function (t) { return '<span class="tag">' + t + '</span>'; }).join('') +
        '</div>' +
        '<div class="project-title">' + proj.title + '</div>' +
        '<div class="project-headline">' + proj.headline + '</div>' +
        '</div></div></div>';
    }).join('');
  }

  /* =========================================================
     PAGE DETECTION
  ========================================================= */
  function getPage() {
    var path = window.location.pathname.toLowerCase();
    var file = path.split('/').pop() || 'index.html';
    if (file === '' || file === 'index.html') return 'index';
    if (file.indexOf('academic') !== -1) return 'academic';
    if (file.indexOf('skill') !== -1) return 'skill';
    if (file.indexOf('experience') !== -1) return 'experience';
    if (file.indexOf('project') !== -1) return 'project';
    return 'index';
  }

  /* =========================================================
     MAIN — fetch JSON and render
  ========================================================= */
  fetch('data/portfolio.json?v=' + Date.now())
    .then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(function (data) {
      applyLabels(data);
      var page = getPage();
      if (page === 'index')      renderIndex(data);
      if (page === 'academic')   renderAcademic(data);
      if (page === 'skill')      renderSkill(data);
      if (page === 'experience') renderExperience(data);
      if (page === 'project')    renderProject(data);

      /* Re-trigger scroll animations for newly rendered elements */
      setTimeout(reObserve, 50);
    })
    .catch(function (err) {
      console.warn('[loader.js] Could not load portfolio data:', err);
    });

})();
