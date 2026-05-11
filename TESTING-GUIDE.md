# Testing Guide - Design System Implementation

**Date:** May 11, 2026  
**Status:** Ready for Testing  
**Build:** ✅ Successful

---

## 🎯 Testing Objectives

Verify that the design system implementation:
1. ✅ Maintains visual consistency across all pages
2. ✅ Improves typography hierarchy and readability
3. ✅ Ensures accessibility compliance (WCAG AA)
4. ✅ Works correctly on all devices and browsers
5. ✅ Doesn't break existing functionality

---

## 🌐 Test URLs

Base URL: `http://rspkudev.test/`

### Priority Pages to Test

#### **Homepage**
- URL: `http://rspkudev.test/`
- Focus: Hero section, service cards, metrics, reviews
- Check: Font sizes, spacing, border radius, touch targets

#### **Single Post (Article)**
- URL: `http://rspkudev.test/berita-artikel/[any-article]/`
- Focus: Article header, metadata, author bio, related articles
- Check: Typography hierarchy, line heights, readability

#### **Single Rawat Inap (Room)**
- URL: `http://rspkudev.test/rawat-inap/vip-shofa-1/`
- Focus: Room details, info cards, sidebar
- Check: Component consistency, spacing, icons

#### **Doctor Archive**
- URL: `http://rspkudev.test/dokter/`
- Focus: Search, filters, doctor cards
- Check: Card consistency, spacing

#### **Journal Archive**
- URL: `http://rspkudev.test/e-jurnal/`
- Focus: Hero stats, journal list
- Check: Typography scale, spacing

#### **Service Archive**
- URL: `http://rspkudev.test/layanan/`
- Focus: Service cards, filters
- Check: Card consistency, border radius

---

## 📋 Visual Testing Checklist

### Typography
- [ ] All headings use standard sizes (xl, 2xl, 3xl, 4xl, 5xl)
- [ ] Body text uses `text-base` (16px)
- [ ] Small text uses `text-sm` (14px)
- [ ] Meta text uses `text-xs` (12px)
- [ ] No arbitrary font sizes visible (e.g., `text-[1.45rem]`)
- [ ] Clear visual hierarchy between heading levels
- [ ] Line heights are appropriate (tight for headings, relaxed for body)

### Border Radius
- [ ] Buttons use `rounded-sm` (12px)
- [ ] Cards use `rounded` or `rounded-lg` (16-24px)
- [ ] Hero sections use `rounded-xl` (32px)
- [ ] Avatars use `rounded-full`
- [ ] No arbitrary border radius values visible

### Spacing
- [ ] Consistent spacing between sections
- [ ] Card padding is uniform (p-6 or p-8)
- [ ] Vertical spacing uses standard scale (space-y-4, space-y-6, space-y-8)
- [ ] No awkward gaps or cramped areas
- [ ] Margins are consistent (mt-4, mt-6, mt-8)

### Colors
- [ ] Body text is readable (slate-600 or darker)
- [ ] Headings are prominent (slate-950)
- [ ] Meta text is subtle but readable (slate-500)
- [ ] Primary actions use hospital-600
- [ ] Hover states are visible

---

## ♿ Accessibility Testing

### Touch Targets
- [ ] All buttons are at least 44x44px
- [ ] Navigation links are at least 44px tall
- [ ] Social media icons are at least 44x44px
- [ ] Icon-only buttons have proper size
- [ ] Mobile: All interactive elements are easily tappable

### Color Contrast
- [ ] Body text meets WCAG AA (4.5:1 minimum)
- [ ] Headings meet WCAG AA (4.5:1 minimum)
- [ ] Links are distinguishable
- [ ] Buttons have sufficient contrast
- [ ] Meta text is readable (borderline acceptable)

### ARIA Labels
- [ ] Icon-only buttons have aria-label
- [ ] Search inputs have labels (visible or sr-only)
- [ ] Navigation landmarks are properly labeled
- [ ] Images have alt text

### Keyboard Navigation
- [ ] All interactive elements are keyboard accessible
- [ ] Focus states are visible
- [ ] Tab order is logical
- [ ] Dropdowns work with keyboard

---

## 📱 Responsive Testing

### Mobile (320px - 767px)
- [ ] Typography scales appropriately
- [ ] Touch targets are large enough (44px minimum)
- [ ] Cards stack properly (1 column)
- [ ] Navigation is accessible
- [ ] Hero section is readable
- [ ] Images don't overflow
- [ ] Spacing is comfortable

### Tablet (768px - 1023px)
- [ ] Cards display in 2 columns where appropriate
- [ ] Typography is comfortable
- [ ] Navigation works well
- [ ] Spacing is balanced
- [ ] Images scale properly

### Desktop (1024px+)
- [ ] Cards display in 3-4 columns where appropriate
- [ ] Typography is optimal
- [ ] Navigation is clear
- [ ] Spacing is generous
- [ ] Layout uses available space well

---

## 🌐 Browser Testing

### Chrome (Latest)
- [ ] All styles render correctly
- [ ] Fonts load properly
- [ ] Animations work smoothly
- [ ] No console errors

### Firefox (Latest)
- [ ] All styles render correctly
- [ ] Fonts load properly
- [ ] Animations work smoothly
- [ ] No console errors

### Safari (Latest)
- [ ] All styles render correctly
- [ ] Fonts load properly
- [ ] Animations work smoothly
- [ ] No console errors

### Edge (Latest)
- [ ] All styles render correctly
- [ ] Fonts load properly
- [ ] Animations work smoothly
- [ ] No console errors

---

## 🔍 Component-Specific Tests

### Buttons
- [ ] Primary buttons are prominent (hospital-600 bg)
- [ ] Secondary buttons are distinct (border style)
- [ ] Hover states work correctly
- [ ] Icons align properly
- [ ] Text is readable
- [ ] Border radius is consistent (rounded-sm)

### Cards
- [ ] Border radius is consistent (rounded-lg)
- [ ] Padding is uniform (p-6 or p-8)
- [ ] Shadows are subtle
- [ ] Hover states work (if applicable)
- [ ] Content hierarchy is clear
- [ ] Images fit properly

### Navigation
- [ ] Desktop menu works correctly
- [ ] Mobile menu works correctly
- [ ] Dropdowns open/close properly
- [ ] Active states are visible
- [ ] Hover states work
- [ ] Touch targets are adequate

### Forms
- [ ] Input fields have proper height (min-h-11)
- [ ] Labels are visible or have sr-only
- [ ] Focus states are clear
- [ ] Error states are visible
- [ ] Submit buttons are prominent

### Hero Sections
- [ ] Typography is impactful (text-5xl)
- [ ] Border radius is large (rounded-xl)
- [ ] Spacing is generous
- [ ] Images scale properly
- [ ] CTAs are prominent

---

## 🐛 Known Issues to Watch For

### Potential Issues
1. **Font Loading:** Ensure custom fonts load correctly
2. **Image Aspect Ratios:** Check that images don't distort
3. **Overflow:** Watch for text or images overflowing containers
4. **Z-Index:** Ensure dropdowns and modals layer correctly
5. **Whitespace:** Look for awkward gaps or cramped areas

### Regression Tests
- [ ] Article reading time displays correctly
- [ ] Author bio card shows properly
- [ ] Related articles load
- [ ] Room info cards display correctly
- [ ] Doctor search works
- [ ] Review carousel functions
- [ ] Social share buttons work

---

## 📊 Performance Testing

### Page Load
- [ ] Homepage loads in < 3 seconds
- [ ] Single post loads in < 2 seconds
- [ ] Archive pages load in < 3 seconds
- [ ] Images lazy load properly
- [ ] No layout shift (CLS)

### CSS Bundle
- [ ] CSS file size is reasonable
- [ ] No unused CSS (check with DevTools)
- [ ] Styles are minified in production

---

## ✅ Acceptance Criteria

### Must Pass
- ✅ All typography uses standard scale (no arbitrary values)
- ✅ All border radius uses standard values (sm, md, lg, xl)
- ✅ All touch targets meet 44px minimum
- ✅ All text meets WCAG AA contrast (4.5:1)
- ✅ No console errors
- ✅ No visual regressions
- ✅ Responsive on all devices
- ✅ Works in all major browsers

### Should Pass
- ✅ Page load times are fast
- ✅ Animations are smooth
- ✅ Images load efficiently
- ✅ No layout shift

---

## 🔧 Testing Tools

### Browser DevTools
```
1. Open DevTools (F12)
2. Check Console for errors
3. Use Lighthouse for accessibility audit
4. Test responsive design mode
5. Check network tab for performance
```

### Accessibility Testing
```
1. Use browser's built-in accessibility checker
2. Test with keyboard only (no mouse)
3. Use screen reader (NVDA, JAWS, VoiceOver)
4. Check color contrast with DevTools
```

### Visual Regression
```
1. Compare screenshots before/after
2. Check for layout shifts
3. Verify spacing consistency
4. Confirm typography hierarchy
```

---

## 📝 Bug Report Template

If you find issues, report them with:

```markdown
**Page:** [URL]
**Browser:** [Chrome/Firefox/Safari/Edge + version]
**Device:** [Desktop/Tablet/Mobile + screen size]
**Issue:** [Description]
**Expected:** [What should happen]
**Actual:** [What actually happens]
**Screenshot:** [If applicable]
**Steps to Reproduce:**
1. Step 1
2. Step 2
3. Step 3
```

---

## 🎯 Test Scenarios

### Scenario 1: Homepage First Visit
1. Navigate to `http://rspkudev.test/`
2. Verify hero section displays correctly
3. Check service cards have consistent styling
4. Verify metrics section is readable
5. Test review carousel functionality
6. Check footer displays properly

### Scenario 2: Article Reading
1. Navigate to any article
2. Verify article header is clear
3. Check metadata is readable
4. Verify content typography is comfortable
5. Check author bio card displays correctly
6. Verify related articles load

### Scenario 3: Room Browsing
1. Navigate to `http://rspkudev.test/rawat-inap/`
2. Browse room listings
3. Click on a room
4. Verify info cards display correctly
5. Check sidebar information is clear
6. Test CTA button

### Scenario 4: Doctor Search
1. Navigate to `http://rspkudev.test/dokter/`
2. Use search functionality
3. Apply filters
4. Verify doctor cards display consistently
5. Check pagination works

### Scenario 5: Mobile Navigation
1. Open site on mobile device
2. Test hamburger menu
3. Navigate through sections
4. Verify touch targets are adequate
5. Check forms are usable

---

## 📈 Success Metrics

### Visual Consistency
- **Target:** 95% of elements use standard design tokens
- **Measure:** Manual inspection of key pages

### Accessibility
- **Target:** WCAG AA compliance (95%+)
- **Measure:** Lighthouse accessibility score

### Performance
- **Target:** Page load < 3 seconds
- **Measure:** Lighthouse performance score

### User Experience
- **Target:** No visual regressions
- **Measure:** Side-by-side comparison

---

## 🚀 Post-Testing Actions

### If All Tests Pass
1. ✅ Mark implementation as complete
2. ✅ Document any minor issues for future fixes
3. ✅ Proceed to Week 2 planning
4. ✅ Share results with team

### If Issues Found
1. 🔴 Document all issues
2. 🔴 Prioritize by severity
3. 🔴 Fix critical issues immediately
4. 🔴 Schedule minor fixes
5. 🔴 Re-test after fixes

---

## 📚 Reference Documents

- **Design System:** `DESIGN-SYSTEM.md`
- **Implementation Summary:** `DESIGN-SYSTEM-IMPLEMENTATION-SUMMARY.md`
- **Before/After Comparison:** `BEFORE-AFTER-COMPARISON.md`
- **Audit Report:** `UI-UX-COMPREHENSIVE-AUDIT.md`

---

## 🏁 Testing Completion

**Tester:** _______________  
**Date:** _______________  
**Status:** [ ] Pass [ ] Fail [ ] Pass with Minor Issues  
**Notes:** _______________

---

**Happy Testing! 🎉**

If you encounter any issues, refer to the bug report template above and document them clearly for quick resolution.
