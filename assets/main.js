/* =============================================
   PORTFOLIO — main.js
   Shared JavaScript: nav, data loader, utils
   ============================================= */

/* ---------- Navbar mobile toggle ---------- */
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  const links  = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => links.classList.toggle('open'));
  }

  /* Close mobile menu on link click */
  document.querySelectorAll('.nav-link, .dropdown a').forEach(el => {
    el.addEventListener('click', () => links && links.classList.remove('open'));
  });

  /* Animate progress bars when visible */
  animateOnScroll();

  /* Smooth scroll for btn-scroll */
  document.querySelectorAll('[data-scroll]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.querySelector(btn.dataset.scroll);
      if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
  });
});

/* ---------- Intersection Observer for animations ---------- */
function animateOnScroll() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        /* Progress bar fill */
        entry.target.querySelectorAll('.progress-fill').forEach(bar => {
          bar.style.width = bar.dataset.width + '%';
        });
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  document.querySelectorAll('.animate').forEach(el => observer.observe(el));

  /* Also observe progress bar containers directly */
  document.querySelectorAll('.progress-fill').forEach(bar => {
    const section = bar.closest('.animate') || bar.closest('.card') || bar;
    if (!bar.closest('.animate')) {
      const obs2 = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            bar.style.width = bar.dataset.width + '%';
            obs2.unobserve(e.target);
          }
        });
      }, { threshold: 0.3 });
      obs2.observe(bar);
    }
  });
}

/* ---------- Expand / Collapse panels ---------- */
function toggleExpand(btn) {
  const panel = btn.closest('.skill-card').querySelector('.expand-panel');
  const icon  = btn.querySelector('.expand-icon');
  const isOpen = panel.classList.contains('open');

  /* Close all others */
  document.querySelectorAll('.expand-panel.open').forEach(p => {
    p.classList.remove('open');
    const b = p.closest('.skill-card')?.querySelector('.expand-icon');
    if (b) b.style.transform = 'rotate(0deg)';
  });

  if (!isOpen) {
    panel.classList.add('open');
    if (icon) icon.style.transform = 'rotate(180deg)';
  }
}

/* ---------- Project Modal ---------- */
function openModal(projectId) {
  const overlay = document.getElementById('project-modal');
  const data = window.portfolioProjects || [];
  const project = data.find(p => p.id === projectId);
  if (!project || !overlay) return;

  overlay.querySelector('#modal-title').textContent = project.title;
  overlay.querySelector('#modal-headline').textContent = project.headline;
  overlay.querySelector('#modal-year').textContent = project.year;
  overlay.querySelector('#modal-desc').textContent = project.description;

  const tagsEl = overlay.querySelector('#modal-tags');
  tagsEl.innerHTML = project.tags.map(t => `<span class="tag">${t}</span>`).join('');

  const linkEl = overlay.querySelector('#modal-link');
  if (project.externalLink) {
    linkEl.href = project.externalLink;
    linkEl.style.display = 'inline-flex';
  } else {
    linkEl.style.display = 'none';
  }

  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  const overlay = document.getElementById('project-modal');
  if (overlay) overlay.classList.remove('open');
  document.body.style.overflow = '';
}

/* Close on backdrop click */
document.addEventListener('click', (e) => {
  if (e.target.id === 'project-modal') closeModal();
});

/* Close on Escape */
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeModal();
});

/* ---------- Tag filter for projects ---------- */
function initTagFilter() {
  const tags = document.querySelectorAll('.filter-tag');
  const items = document.querySelectorAll('.project-item');

  tags.forEach(tag => {
    tag.addEventListener('click', () => {
      tags.forEach(t => t.classList.remove('active'));
      tag.classList.add('active');

      const selected = tag.dataset.tag;
      items.forEach(item => {
        if (selected === 'All' || item.dataset.tags.includes(selected)) {
          item.style.display = '';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });
}

/* ---------- JSON data loader (optional utility) ---------- */
async function loadPortfolioData() {
  try {
    const res = await fetch('../data/portfolio.json');
    return await res.json();
  } catch {
    return null;
  }
}

/* ---------- CSS animation helper ---------- */
const style = document.createElement('style');
style.textContent = `
  .animate { opacity: 0; transform: translateY(24px); transition: opacity 0.5s ease, transform 0.5s ease; }
  .animate.visible { opacity: 1; transform: translateY(0); }
`;
document.head.appendChild(style);
