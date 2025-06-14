import React, { useState } from 'react';
import styles from './Dropdown.module.css';
import { motion, AnimatePresence } from 'framer-motion';

const Dropdown = ({ items }) => {
  const [isOpen, setIsOpen] = useState(false);

  const dropdownVariants = {
    hidden: {
      opacity: 0,
      y: -20,
      transition: { duration: 0.3, ease: "easeInOut" }
    },
    visible: {
      opacity: 1,
      y: 0,
      transition: { duration: 0.3, ease: "easeInOut" }
    },
  };

  return (
    <div className={styles.dropdownContainer}>
      <button className={styles.dropdownTrigger} onClick={() => setIsOpen(!isOpen)}>
        <span>Add tools</span>
        <div className={styles.chevronWrapper}>
          <motion.svg
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            animate={{ rotate: isOpen ? 180 : 0 }}
            transition={{ duration: 0.3 }}
            style={{ originX: '50%', originY: '50%' }}
          >
            <path d="M4 11L8 7L12 11" stroke="#8A6CFF" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </motion.svg>
        </div>
      </button>

      <AnimatePresence>
        {isOpen && (
          <motion.ul
            className={styles.dropdownMenu}
            initial="hidden"
            animate="visible"
            exit="hidden"
            variants={dropdownVariants}
          >
            {items.map((item, index) => (
              <li key={index} className={styles.dropdownItem}>
                {item}
              </li>
            ))}
          </motion.ul>
        )}
      </AnimatePresence>
    </div>
  );
};

export default Dropdown;
