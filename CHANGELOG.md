# Changelog

## Version 1.1.0 - 2026-01-12

### Fixed
- **Button Visibility**: Fixed expand/collapse buttons not appearing on mobile devices and small screens
  - Added CSS rules to properly show expand button when panel is closed
  - Added CSS rules to show collapse button when panel is open
  - Buttons now properly toggle visibility based on offcanvas state
- **Mobile/Responsive Functionality**: Fixed collapse/expand buttons not appearing on actual mobile devices and small screens
  - Added proper CSS media queries (`@media (max-width: 767px)`) for frontend mobile devices
  - Previous version only worked in Elementor editor preview mode
  - Now works on both actual mobile devices AND Elementor editor preview
- **Icon Rendering**: Added validation to prevent PHP warnings when icon data is null or empty

### Added
- **Tablet Support**: Added tablet support in Elementor editor preview mode
- **Escape Key**: Press ESC key to close the offcanvas panel
- **Body Scroll Lock**: Prevents background scrolling when offcanvas is open
- **Better Animations**: Improved CSS transitions (300ms ease-in-out instead of 200ms linear)

### Improved
- **Accessibility**: Changed button `tabindex` from `-1` to `0` for better keyboard navigation
- **UX**: Added `cursor: pointer` to trigger buttons for better user feedback
- **Width Control**: Width slider now works on both frontend and Elementor editor
- **Icon Sizing**: Fixed icon size controls to be more specific and reliable

### Compatibility
- Tested and confirmed working with Elementor 3.34.x
- Supports both Elementor Columns and Containers
- Works with WordPress admin bar

---

## Version 1.0.0
- Initial release
