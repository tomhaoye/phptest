#!/usr/bin/python
import sys

def _fmtEncode(fmt):
    '''Encode the 15-bit format code using BCH code.'''
    g = 0x537
    code = fmt << 10
    for i in range(4,-1,-1):
        if code & (1 << (i+10)):
            code ^= g << i
    return ((fmt << 10) ^ code) ^ 0b101010000010010

def _fillInfo(mask):
    '''
    Fill the encoded format code into the masked QR code matrix.
    mask: (masked QR code matrix, mask number).
    '''
    # 01 is the format code for L error control level,
    # concatenated with mask id and passed into _fmtEncode
    # to get the 15 bits format code with EC bits.
    fmt = _fmtEncode(int('01'+'{:03b}'.format(mask), 2))
    fmtarr = [int(c) for c in '{:015b}'.format(fmt)]
    return fmtarr

if __name__ == "__main__":
    res = sys.argv[1]
    print _fillInfo(int(res, 2))