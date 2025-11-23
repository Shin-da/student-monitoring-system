#!/usr/bin/env python3
"""
PDF Text Extraction Script
Extracts readable text from Chapter 1-3 REVISED.pdf
"""

import sys
import os

def extract_with_pypdf2():
    """Try extracting with PyPDF2"""
    try:
        import PyPDF2
        
        pdf_path = "Chapter 1-3 REVISED.pdf"
        if not os.path.exists(pdf_path):
            print(f"Error: {pdf_path} not found")
            return None
        
        text_content = []
        with open(pdf_path, 'rb') as file:
            pdf_reader = PyPDF2.PdfReader(file)
            num_pages = len(pdf_reader.pages)
            
            print(f"Found {num_pages} pages in PDF")
            
            for page_num in range(min(num_pages, 50)):  # Limit to first 50 pages
                try:
                    page = pdf_reader.pages[page_num]
                    text = page.extract_text()
                    if text.strip():
                        text_content.append(f"\n--- Page {page_num + 1} ---\n")
                        text_content.append(text)
                except Exception as e:
                    print(f"Error extracting page {page_num + 1}: {e}")
        
        return '\n'.join(text_content)
    except ImportError:
        return None
    except Exception as e:
        print(f"Error with PyPDF2: {e}")
        return None

def extract_with_pdfplumber():
    """Try extracting with pdfplumber"""
    try:
        import pdfplumber
        
        pdf_path = "Chapter 1-3 REVISED.pdf"
        if not os.path.exists(pdf_path):
            print(f"Error: {pdf_path} not found")
            return None
        
        text_content = []
        with pdfplumber.open(pdf_path) as pdf:
            num_pages = len(pdf.pages)
            print(f"Found {num_pages} pages in PDF")
            
            for page_num in range(min(num_pages, 50)):  # Limit to first 50 pages
                try:
                    page = pdf.pages[page_num]
                    text = page.extract_text()
                    if text and text.strip():
                        text_content.append(f"\n--- Page {page_num + 1} ---\n")
                        text_content.append(text)
                except Exception as e:
                    print(f"Error extracting page {page_num + 1}: {e}")
        
        return '\n'.join(text_content)
    except ImportError:
        return None
    except Exception as e:
        print(f"Error with pdfplumber: {e}")
        return None

def main():
    print("Attempting to extract text from PDF...")
    
    # Try pdfplumber first (better for complex PDFs)
    text = extract_with_pdfplumber()
    if text:
        output_file = "research_document_extracted_text.txt"
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(text)
        print(f"\n✓ Successfully extracted text to {output_file}")
        print(f"Extracted {len(text)} characters")
        return
    
    # Try PyPDF2 as fallback
    text = extract_with_pypdf2()
    if text:
        output_file = "research_document_extracted_text.txt"
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(text)
        print(f"\n✓ Successfully extracted text to {output_file}")
        print(f"Extracted {len(text)} characters")
        return
    
    # If both fail, suggest installation
    print("\n✗ No PDF extraction library found.")
    print("\nTo install PDF extraction libraries, run:")
    print("  pip install pdfplumber")
    print("  OR")
    print("  pip install PyPDF2")
    print("\nThen run this script again.")

if __name__ == "__main__":
    main()

